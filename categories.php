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
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    if ($action === 'delete') {
        if (!$id) { msg('ID kategori tidak valid.', 'error'); go('categories.php'); }
        try {
            $s = $pdo->prepare('DELETE FROM categories WHERE id=?');
            $s->execute([$id]);
            msg('Kategori dihapus.');
        } catch (PDOException $e) {
            msg('Kategori tidak dapat dihapus karena masih digunakan oleh produk.', 'error');
        }
        go('categories.php');
    }
    if ($name === '' || strlen($name) > 100) {
        msg('Nama kategori wajib diisi (maksimal 100 karakter).', 'error');
        go($action === 'update' && $id ? 'categories.php?edit='.$id : 'categories.php');
    }
    try {
        if ($action === 'update' && $id) {
            $s = $pdo->prepare('UPDATE categories SET name=?,description=? WHERE id=?');
            $s->execute([$name, $description ?: null, $id]);
            msg('Kategori diperbarui.');
        } else {
            $s = $pdo->prepare('INSERT INTO categories(name,description) VALUES(?,?)');
            $s->execute([$name, $description ?: null]);
            msg('Kategori ditambahkan.');
        }
    } catch (PDOException $e) {
        msg('Nama kategori sudah digunakan atau data tidak valid.', 'error');
    }
    go('categories.php');
}
$editing = null;
if (isset($_GET['edit'])) {
    admin_required();
    $id = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
    if ($id) {
        $s = $pdo->prepare('SELECT * FROM categories WHERE id=?');
        $s->execute([$id]);
        $editing = $s->fetch();
    }
    if (!$editing) { msg('Kategori tidak ditemukan.', 'error'); go('categories.php'); }
}
$rows = $pdo->query('SELECT c.id,c.name,c.description,COUNT(p.id) total FROM categories c LEFT JOIN products p ON p.category_id=c.id GROUP BY c.id ORDER BY c.name')->fetchAll();
header_html('Kategori');
if ($isAdmin) {
    echo '<section><h2>'.($editing ? 'Edit Kategori' : 'Tambah Kategori').'</h2><form method="post"><input type="hidden" name="csrf" value="'.e(token()).'"><input type="hidden" name="action" value="'.($editing?'update':'create').'">';
    if ($editing) echo '<input type="hidden" name="id" value="'.e($editing['id']).'">';
    echo '<label>Nama kategori</label><input name="name" maxlength="100" required value="'.e($editing['name'] ?? '').'"><label>Deskripsi</label><input name="description" value="'.e($editing['description'] ?? '').'"><button>Simpan</button>';
    if ($editing) echo ' <a href="categories.php">Batal</a>';
    echo '</form></section>';
}
echo '<section><table><tr><th>Nama</th><th>Deskripsi</th><th>Produk</th>';
if ($isAdmin) echo '<th>Aksi</th>';
echo '</tr>';
foreach ($rows as $r) {
    echo '<tr><td>'.e($r['name']).'</td><td>'.e($r['description']).'</td><td>'.e($r['total']).'</td>';
    if ($isAdmin) echo '<td><a href="categories.php?edit='.e($r['id']).'">Edit</a> <form method="post" style="display:inline" onsubmit="return confirm(\'Hapus kategori ini?\')"><input type="hidden" name="csrf" value="'.e(token()).'"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="'.e($r['id']).'"><button>Hapus</button></form></td>';
    echo '</tr>';
}
echo '</table></section>';
footer_html();
