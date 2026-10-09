<?php
if(session_status()!==PHP_SESSION_ACTIVE) session_start();
function e($v): string { return htmlspecialchars((string)($v??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
function go($url): never { header('Location: '.$url); exit; }
function token(): string { if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function check_token(): void { if(!hash_equals(token(),(string)($_POST['csrf']??''))){http_response_code(403);exit('Permintaan tidak valid.');} }
function msg($s,$type='ok'): void { $_SESSION['msg']=['s'=>$s,'t'=>$type]; }
function flash(): void { if(isset($_SESSION['msg'])){echo '<p class="alert '.e($_SESSION['msg']['t']).'">'.e($_SESSION['msg']['s']).'</p>';unset($_SESSION['msg']);} }
function login_required(): void { if(empty($_SESSION['user']))go('login.php'); }
function admin_required(): void { login_required(); if($_SESSION['user']['role']!=='admin'){http_response_code(403);exit('Khusus admin.');} }
function header_html($title): void { echo '<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.e($title).'</title><link rel="stylesheet" href="assets/style.css"></head><body>'; if(!empty($_SESSION['user'])){echo '<nav><b>Inventory App</b> <a href="dashboard.php">Dashboard</a> <a href="products.php">Produk</a> <a href="categories.php">Kategori</a> <a href="suppliers.php">Pemasok</a> <a href="stock.php">Stok</a> <a href="reports.php">Laporan</a> ';if($_SESSION['user']['role']==='admin')echo '<a href="users.php">Pengguna</a>';echo '<a href="logout.php">Keluar</a></nav>';}echo '<main><h1>'.e($title).'</h1>';flash(); }
function footer_html(): void { echo '</main></body></html>'; }
