# Inventory Management System

Aplikasi web **Inventory Management System** merupakan aplikasi untuk membantu pengelolaan data persediaan barang secara terstruktur menggunakan PHP dan MySQL. Aplikasi ini menyediakan fitur pengelolaan produk, kategori, pemasok, stok barang, serta laporan persediaan.

## Fitur Utama

- **Dashboard:** menampilkan ringkasan informasi persediaan.
- **Manajemen Produk:** menambah, melihat, mengedit, dan menghapus data produk.
- **Manajemen Kategori:** mengelola kategori produk.
- **Manajemen Pemasok:** mengelola data pemasok dan hubungan pemasok dengan produk.
- **Manajemen Stok:** mencatat perubahan stok dan menjaga riwayat pergerakan stok.
- **Pencarian dan Pengurutan:** memudahkan pencarian produk berdasarkan nama atau SKU serta pengurutan data.
- **Laporan:** membantu melihat informasi persediaan.
- **Login dan Hak Akses:** membedakan akses pengguna berdasarkan peran, seperti admin dan staff.

## Teknologi yang Digunakan

- PHP
- MySQL
- HTML dan CSS
- XAMPP
- Visual Studio Code

## Struktur Proyek

- `config/` — konfigurasi koneksi database.
- `helpers/` — fungsi pendukung aplikasi.
- `products.php` — halaman pengelolaan produk.
- `categories.php` — halaman pengelolaan kategori.
- `suppliers.php` — halaman pengelolaan pemasok.
- `stock.php` — halaman pengelolaan stok.
- `reports.php` — halaman laporan.
- `dashboard.php` — halaman dashboard.
- `login.php` dan `logout.php` — pengelolaan sesi pengguna.

## Cara Menjalankan Aplikasi

1. Instal dan jalankan Apache serta MySQL melalui XAMPP.
2. Letakkan folder proyek di direktori `htdocs`.
3. Buka phpMyAdmin melalui `http://localhost/phpmyadmin`.
4. Buat database bernama `inventory_app`.
5. Impor file SQL awal proyek yang tersedia.
6. Jika tabel relasi pemasok produk belum tersedia, jalankan `migration_product_suppliers.sql` pada database `inventory_app`.
7. Periksa pengaturan koneksi database pada `config/database.php`.
8. Buka aplikasi melalui `http://localhost/inventory-app/`.
9. Login menggunakan akun yang telah disiapkan pada database.

## Keamanan dan Pengelolaan Data

Aplikasi menggunakan sesi login, pembatasan hak akses, validasi input, dan perlindungan CSRF pada formulir yang mendukungnya. Penghapusan produk yang memiliki riwayat transaksi stok dibatasi agar riwayat persediaan tetap terjaga.

## Tujuan Proyek

Proyek ini dibuat sebagai sarana pembelajaran pengembangan aplikasi web menggunakan PHP dan MySQL, khususnya dalam penerapan operasi CRUD (*Create, Read, Update, Delete*), relasi database, pengelolaan persediaan, dan autentikasi pengguna.