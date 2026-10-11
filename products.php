<?php
require 'config/database.php';
require 'helpers/functions.php';
login_required();

$allowedSort = ['name'=>'p.name','sku'=>'p.sku','price'=>'p.price','stock'=>'p.stock'];
$q = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'name';
if (!isset($allowedSort[$sort])) $sort = 'name';
$dir = strtolower($_GET['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;
$isAdmin = ($_SESSION['user']['role'] ?? '') === 'admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_token();
    $action = $_POST['action'] ?? 'create';
    if ($action === 'delete') {
        admin_required();
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$id) { msg('ID produk tidak valid.', 'error'); go('products.php'); }
        try {
            $pdo->beginTransaction();
            $check = $pdo->prepare('SELECT COUNT(*) FROM stock_movements WHERE product_id=?');
            $check->execute([$id]);
            if ((int)$check->fetchColumn() > 0) {
                throw new RuntimeException('Produk memiliki riwayat transaksi. Produk tidak dapat dihapus agar riwayat tetap aman.');
            }
            $pdo->prepare('DELETE FROM product_suppliers WHERE product_id=?')->execute([$id]);
            $del = $pdo->prepare('DELETE FROM products WHERE id=?');
            $del->execute([$id]);
            $pdo->commit();
            msg('Produk dihapus.');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            msg($e instanceof RuntimeException ? $e->getMessage() : 'Produk tidak dapat dihapus. Periksa relasi data.', 'error');
        }
        go('products.php');
    }

    if ($action === 'create' || $action === 'update') {
        if ($action === 'update') admin_required();
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $sku = trim($_POST['sku'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $cat = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
        $supplierIds = $_POST['supplier_ids'] ?? [];
        if (!is_array($supplierIds)) $supplierIds = [];
        $supplierIds = array_values(array_unique(array_filter(array_map('intval', $supplierIds), fn($v) => $v > 0)));
        $priceRaw = $_POST['price'] ?? '';
        $price = filter_var($priceRaw, FILTER_VALIDATE_FLOAT);
        $valid = $sku !== '' && $name !== '' && $cat && $price !== false && $price >= 0
            && strlen($sku) <= 50 && strlen($name) <= 150;
        if (!$valid) {
            msg('Data tidak valid. SKU, nama, dan kategori wajib diisi; harga tidak boleh negatif.', 'error');
            go($action === 'update' && $id ? 'products.php?edit='.$id : 'products.php');
        }
        try {
            $catCheck = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE id=?');
            $catCheck->execute([$cat]);
            if (!(int)$catCheck->fetchColumn()) throw new RuntimeException('Kategori tidak ditemukan.');
            if ($supplierIds) {
                $marks = implode(',', array_fill(0, count($supplierIds), '?'));
                $supCheck = $pdo->prepare("SELECT COUNT(*) FROM suppliers WHERE id IN ($marks)");
                $supCheck->execute($supplierIds);
                if ((int)$supCheck->fetchColumn() !== count($supplierIds)) throw new RuntimeException('Ada pemasok yang tidak valid.');
            }
            $legacySupplier = $supplierIds[0] ?? null;
            $pdo->beginTransaction();
            if ($action === 'update') {
                if (!$id) throw new RuntimeException('ID produk tidak valid.');
                $upd = $pdo->prepare('UPDATE products SET sku=?,name=?,category_id=?,supplier_id=?,price=? WHERE id=?');
                $upd->execute([$sku,$name,$cat,$legacySupplier,$price,$id]);
                $pdo->prepare('DELETE FROM product_suppliers WHERE product_id=?')->execute([$id]);
                $productId = $id;
            } else {
                $initialStock = filter_var($_POST['stock'] ?? '0', FILTER_VALIDATE_INT);
                if ($initialStock === false || $initialStock < 0) throw new RuntimeException('Stok awal tidak boleh negatif.');
                $ins = $pdo->prepare('INSERT INTO products(sku,name,category_id,supplier_id,price,stock) VALUES(?,?,?,?,?,?)');
                $ins->execute([$sku,$name,$cat,$legacySupplier,$price,$initialStock]);
                $productId = (int)$pdo->lastInsertId();
            }
            if ($supplierIds) {
                $bridge = $pdo->prepare('INSERT INTO product_suppliers(product_id,supplier_id) VALUES(?,?)');
                foreach ($supplierIds as $supplierId) $bridge->execute([$productId,$supplierId]);
            }
            $pdo->commit();
            msg($action === 'update' ? 'Produk diperbarui.' : 'Produk ditambahkan.');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $message = $e instanceof RuntimeException ? $e->getMessage() : 'Gagal menyimpan. Pastikan SKU unik dan data relasi valid.';
            msg($message, 'error');
        }
        go('products.php');
    }
}

$cats = $pdo->query('SELECT id,name FROM categories ORDER BY name')->fetchAll();
$suppliers = $pdo->query('SELECT id,name FROM suppliers ORDER BY name')->fetchAll();
$editing = null;
$selectedSuppliers = [];
if (isset($_GET['edit'])) {
    admin_required();
    $editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
    if ($editId) {
        $stmt = $pdo->prepare('SELECT * FROM products WHERE id=?');
        $stmt->execute([$editId]);
        $editing = $stmt->fetch();
        if ($editing) {
            try {
                $ss = $pdo->prepare('SELECT supplier_id FROM product_suppliers WHERE product_id=?');
                $ss->execute([$editId]);
                $selectedSuppliers = array_map('intval', array_column($ss->fetchAll(), 'supplier_id'));
            } catch (PDOException $e) {
                $selectedSuppliers = !empty($editing['supplier_id']) ? [(int)$editing['supplier_id']] : [];
            }
        }
    }
    if (!$editing) { msg('Produk tidak ditemukan.', 'error'); go('products.php'); }
}

$where = $q !== '' ? ' WHERE p.name LIKE ? OR p.sku LIKE ?' : '';
$params = $q !== '' ? ["%$q%","%$q%"] : [];
$count = $pdo->prepare("SELECT COUNT(*) FROM products p$where");
$count->execute($params);
$total = (int)$count->fetchColumn();
$pages = max(1, (int)ceil($total / $limit));
$page = min($page, $pages);
$offset = ($page - 1) * $limit;
$sql = "SELECT p.*,c.name category,
        COALESCE(GROUP_CONCAT(DISTINCT sp.name ORDER BY sp.name SEPARATOR ', '), s.name, '-') supplier_names
        FROM products p JOIN categories c ON c.id=p.category_id
        LEFT JOIN suppliers s ON s.id=p.supplier_id
        LEFT JOIN product_suppliers ps ON ps.product_id=p.id
        LEFT JOIN suppliers sp ON sp.id=ps.supplier_id
        $where GROUP BY p.id ORDER BY {$allowedSort[$sort]} $dir LIMIT $limit OFFSET $offset";
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
} catch (PDOException $e) {
    // Clear migration guidance rather than silently showing incomplete supplier data.
    http_response_code(500);
    exit('Tabel product_suppliers belum tersedia. Jalankan file migration_product_suppliers.sql melalui phpMyAdmin, lalu muat ulang halaman.');
}

header_html('Produk');
echo '<section><h2>'.($editing ? 'Edit Produk' : 'Tambah Produk').'</h2><form method="post" class="grid">';
echo '<input type="hidden" name="csrf" value="'.e(token()).'">';
echo '<input type="hidden" name="action" value="'.($editing ? 'update' : 'create').'">';
if ($editing) echo '<input type="hidden" name="id" value="'.e($editing['id']).'">';
echo '<div><label>SKU</label><input name="sku" maxlength="50" required value="'.e($editing['sku'] ?? '').'"></div>';
echo '<div><label>Nama</label><input name="name" maxlength="150" required value="'.e($editing['name'] ?? '').'"></div>';
echo '<div><label>Kategori</label><select name="category_id" required><option value="">Pilih</option>';
foreach ($cats as $c) echo '<option value="'.e($c['id']).'" '.((int)($editing['category_id'] ?? 0)===(int)$c['id']?'selected':'').'>'.e($c['name']).'</option>';
echo '</select></div><div><label>Pemasok (bisa pilih lebih dari satu)</label><select name="supplier_ids[]" multiple size="3">';
foreach ($suppliers as $sp) echo '<option value="'.e($sp['id']).'" '.(in_array((int)$sp['id'],$selectedSuppliers,true)?'selected':'').'>'.e($sp['name']).'</option>';
echo '</select><small>Tahan Ctrl untuk memilih beberapa pemasok.</small></div>';
echo '<div><label>Harga</label><input type="number" min="0" step="0.01" name="price" required value="'.e($editing['price'] ?? '0').'"></div>';
if (!$editing) echo '<div><label>Stok awal</label><input type="number" min="0" name="stock" value="0" required></div>';
else echo '<div><label>Stok saat ini</label><input value="'.e($editing['stock']).'" disabled><small>Perubahan stok dilakukan melalui menu Stok agar riwayat tetap tercatat.</small></div>';
echo '<button>'.($editing ? 'Simpan Perubahan' : 'Tambah Produk').'</button>';
if ($editing) echo '<a href="products.php">Batal</a>';
echo '</form></section>';

echo '<section><h2>Daftar Produk</h2><form class="filters" method="get"><input name="q" placeholder="Cari nama/SKU" value="'.e($q).'"><select name="sort">';
foreach (['name'=>'Nama','sku'=>'SKU','price'=>'Harga','stock'=>'Stok'] as $key=>$label) echo '<option value="'.e($key).'" '.($sort===$key?'selected':'').'>'.e($label).'</option>';
echo '</select><select name="dir"><option value="asc" '.($dir==='ASC'?'selected':'').'>Naik</option><option value="desc" '.($dir==='DESC'?'selected':'').'>Turun</option></select><button>Filter</button></form>';
echo '<table><tr><th>SKU</th><th>Nama</th><th>Kategori</th><th>Pemasok</th><th>Harga</th><th>Stok</th>';
if ($isAdmin) echo '<th>Aksi</th>';
echo '</tr>';
foreach ($rows as $r) {
    echo '<tr><td>'.e($r['sku']).'</td><td>'.e($r['name']).'</td><td>'.e($r['category']).'</td><td>'.e($r['supplier_names']).'</td><td>Rp '.number_format((float)$r['price'],0,',','.').'</td><td>'.e($r['stock']).'</td>';
    if ($isAdmin) {
        echo '<td><a href="products.php?edit='.e($r['id']).'">Edit</a> <form method="post" style="display:inline" onsubmit="return confirm(\'Yakin ingin menghapus produk ini?\')"><input type="hidden" name="csrf" value="'.e(token()).'"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="'.e($r['id']).'"><button type="submit">Hapus</button></form></td>';
    }
    echo '</tr>';
}
if (!$rows) echo '<tr><td colspan="'.($isAdmin?7:6).'">Data kosong.</td></tr>';
echo '</table><p>Halaman '.$page.' dari '.$pages.'</p><div class="pages">';
for ($i=1;$i<=$pages;$i++) { $paramsPage=$_GET; $paramsPage['page']=$i; unset($paramsPage['edit']); echo '<a href="?'.e(http_build_query($paramsPage)).'">'.$i.'</a> '; }
echo '</div></section>';
footer_html();
