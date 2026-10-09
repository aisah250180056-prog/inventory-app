<?php
require 'config/database.php'; require 'helpers/functions.php';
if((int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn()>0){header_html('Setup');echo '<p>Pengguna sudah ada. Hapus file setup_admin.php.</p>';footer_html();exit;}
$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){check_token();$name=trim($_POST['name']??'');$username=trim($_POST['username']??'');$pw=$_POST['password']??'';
if($name===''||$username===''||strlen($pw)<10)$err='Semua kolom wajib. Password minimal 10 karakter.';
else{try{$s=$pdo->prepare("INSERT INTO users(name,username,password,role) VALUES(?,?,?,'admin')");$s->execute([$name,$username,password_hash($pw,PASSWORD_DEFAULT)]);msg('Admin dibuat. Silakan login.');go('login.php');}catch(PDOException $e){$err='Username sudah dipakai.';}}}
header_html('Buat Admin Pertama');if($err)echo '<p class="alert error">'.e($err).'</p>';echo '<section><p>Buat admin pertama. Setelah berhasil, hapus file setup_admin.php.</p><form method="post"><input type="hidden" name="csrf" value="'.e(token()).'"><label>Nama</label><input name="name" required><label>Username</label><input name="username" required><label>Password (minimal 10 karakter)</label><input type="password" name="password" minlength="10" required><button>Buat Admin</button></form></section>';footer_html();
