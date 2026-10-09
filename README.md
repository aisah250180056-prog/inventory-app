# Inventory Management System
## Instalasi
1. Ekstrak folder `inventory-app` ke `C:\xampp\htdocs\inventory-app`.
2. Jalankan Apache dan MySQL di XAMPP.
3. Import `database.sql` melalui `http://localhost/phpmyadmin`.
4. Periksa `config/database.php` (default XAMPP: user root, password kosong).
5. Buka `http://localhost/inventory-app/setup_admin.php`, buat admin.
6. Setelah berhasil, hapus `setup_admin.php`.
7. Buka `http://localhost/inventory-app/login.php`.

Database baru bernama `inventory_app`; database `product_manager` tidak disentuh.
