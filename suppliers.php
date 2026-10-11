<?php
require 'config/database.php';
require 'helpers/functions.php';
login_required();
$isAdmin = ($_SESSION['user']['role'] ?? '') === 'admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_token();
    admin_required();
    $action = $_POST['action'] ?? 'create';
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($action === 'delete') {
        if (!$id) { msg('ID pemasok tidak valid.', 'error'); go('suppliers.php'); }
        try {
            $pdo->beginTransaction();
            $check = $pdo->prepare('SELECT COUNT(*) FROM product_suppliers WHERE supplier_id=?');
            $check->execute([$id]);
            $bridgeCount = (int)$check->fetchColumn();
            $check = $pdo->prepare('SELECT COUNT(*) FROM products WHERE supplier_id=?');
            $check->execute([$id]);
            $legacyCount = (int)$check->fetchColumn();
            if ($bridgeCount || $legacyCount) throw new RuntimeException('Pemasok masih terhubung dengan produk dan tidak dapat dihapus.');
            $del = $pdo->prepare('DELETE FROM suppliers WHERE id=?');
            $del->execute([$id]);
            $pdo->commit();
            msg('Pemasok dihapus.');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            msg($e instanceof RuntimeException ? $e->getMessage() : 'Pemasok tidak dapat dihapus.', 'error');
        }
        go('suppliers.php');
    }
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    if ($name === '' || strlen($name) > 150 || ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))) {
        msg('Nama wajib diisi dan format email harus valid.', 'error');
        go($action === 'update' && $id ? 'suppliers.php?edit='.$id : 'suppliers.php');
    }
    try {
        if ($action === 'update' && $id) {
            $s = $pdo->prepare('UPDATE suppliers SET name=?,phone=?,email=?,address=? WHERE id=?');
            $s->execute([$name,$phone ?: null,$email ?: null,$address ?: null,$id]);
            msg('Pemasok diperbarui.');
        } else {
            $s = $pdo->prepare('INSERT INTO suppliers(name,phone,email,address) VALUES(?,?,?,?)');
            $s->execute([$name,$phone ?: null,$email ?: null,$address ?: null]);
            msg('Pemasok ditambahkan.');
        }
    } catch (PDOException $e) {
        msg('Pemasok gagal disimpan. Periksa data yang dimasukkan.', 'error');
    }
    go('suppliers.php');
}
$editing = null;
if (isset($_GET['edit'])) {
    admin_required();
    $id = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
    if ($id) {
        $s = $pdo->prepare('SELECT * FROM suppliers WHERE id=?');
        $s->execute([$id]);
        $editing = $s->fetch();
    }
    if (!$editing) { msg('Pemasok tidak ditemukan.', 'error'); go('suppliers.php'); }
}
$rows = $pdo->query('SELECT s.*,COUNT(DISTINCT COALESCE(ps.product_id,p.id)) total FROM suppliers s LEFT JOIN products p ON p.supplier_id=s.id LEFT JOIN product_suppliers ps ON ps.supplier_id=s.id GROUP BY s.id ORDER BY s.name')->fetchAll();
header_html('Pemasok');
if ($isAdmin) {
    echo '<section><h2>'.($editing ? 'Edit Pemasok' : 'Tambah Pemasok').'</h2><form method="post" class="grid"><input type="hidden" name="csrf" value="'.e(token()).'"><input type="hidden" name="action" value="'.($editing?'update':'create').'">';
    if ($editing) echo '<input type="hidden" name="id" value="'.e($editing['id']).'">';
    echo '<div><label>Nama</label><input name="name" maxlength="150" required value="'.e($editing['name'] ?? '').'"></div><div><label>Telepon</label><input name="phone" value="'.e($editing['phone'] ?? '').'"></div><div><label>Email</label><input type="email" name="email" value="'.e($editing['email'] ?? '').'"></div><div><label>Alamat</label><input name="address" value="'.e($editing['address'] ?? '').'"></div><button>Simpan</button>';
    if ($editing) echo ' <a href="suppliers.php">Batal</a>';
    echo '</form></section>';
}
echo '<section><table><tr><th>Nama</th><th>Telepon</th><th>Email</th><th>Alamat</th><th>Produk</th>';
if ($isAdmin) echo '<th>Aksi</th>';
echo '</tr>';
foreach ($rows as $r) {
    echo '<tr><td>'.e($r['name']).'</td><td>'.e($r['phone']).'</td><td>'.e($r['email']).'</td><td>'.e($r['address']).'</td><td>'.e($r['total']).'</td>';
    if ($isAdmin) echo '<td><a href="suppliers.php?edit='.e($r['id']).'">Edit</a> <form method="post" style="display:inline" onsubmit="return confirm(\'Hapus pemasok ini?\')"><input type="hidden" name="csrf" value="'.e(token()).'"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="'.e($r['id']).'"><button>Hapus</button></form></td>';
    echo '</tr>';
}
echo '</table></section>';
footer_html();
