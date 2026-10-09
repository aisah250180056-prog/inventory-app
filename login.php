<?php
require 'config/database.php';require 'helpers/functions.php';
if(!empty($_SESSION['user']))go('dashboard.php');$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){check_token();$s=$pdo->prepare('SELECT * FROM users WHERE username=?');$s->execute([trim($_POST['username']??'')]);$u=$s->fetch();if($u&&password_verify($_POST['password']??'',$u['password'])){session_regenerate_id(true);$_SESSION['user']=['id'=>$u['id'],'name'=>$u['name'],'username'=>$u['username'],'role'=>$u['role']];go('dashboard.php');}$err='Username atau password salah.';}
header_html('Login');if($err)echo '<p class="alert error">'.e($err).'</p>';echo '<section class="narrow"><form method="post"><input type="hidden" name="csrf" value="'.e(token()).'"><label>Username</label><input name="username" required><label>Password</label><input type="password" name="password" required><button>Login</button></form></section>';footer_html();
