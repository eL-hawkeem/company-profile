# 🏢 Company Profile Website

Website Company Profile profesional dengan Admin Dashboard yang dibangun menggunakan PHP Native, Bootstrap, dan MySQL.

![PHP](https://img.shields.io/badge/PHP-Native-777BB4?style=flat&logo=php&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.x-7952B3?style=flat&logo=bootstrap&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?style=flat&logo=mysql&logoColor=white)

## 📋 Tentang Project

Website Company Profile ini dirancang untuk membantu perusahaan menampilkan informasi bisnis mereka secara profesional dan mudah dikelola. Website ini dilengkapi dengan admin dashboard yang memudahkan pengelolaan konten tanpa perlu pengetahuan teknis mendalam.

### ✨ Fitur Utama

#### 🌐 Website Utama
- **Beranda** - Tampilan menarik dengan informasi utama perusahaan
- **Tentang Perusahaan** - Profil lengkap dan visi misi perusahaan
- **Produk** - Katalog produk dengan deskripsi dan gambar
- **Artikel/Blog** - Konten artikel dan berita perusahaan
- **Pemesanan via WhatsApp** - Redirect otomatis ke WhatsApp untuk pemesanan produk
- **Kontak** - Informasi kontak dan formulir komunikasi

#### 🎛️ Admin Dashboard
- **Manajemen Konten** - Kelola semua konten website utama
- **Manajemen Produk** - Tambah, edit, dan hapus produk
- **Manajemen Artikel** - Kelola artikel dan berita
- **Manajemen Kontak** - Kelola informasi kontak perusahaan
- **Dashboard Analytics** - Statistik website

## 🛠️ Teknologi yang Digunakan

- **Backend**: PHP Native
- **Frontend**: Bootstrap 5.x, HTML5, CSS3, JavaScript
- **Database**: MySQL
- **Icons**: Font Awesome / Bootstrap Icons
- **jQuery**: Untuk interaksi dinamis

## 📦 Prasyarat

Sebelum melakukan instalasi, pastikan server Anda sudah memiliki:

- PHP 7.4 atau lebih tinggi
- MySQL 5.7 atau lebih tinggi
- Apache/Nginx Web Server
- phpMyAdmin (opsional, untuk manajemen database)

## 🚀 Instalasi

### 1. Clone Repository

```bash
git clone https://github.com/eL-hawkeem/company-profile.git
cd company-profile
```

### 2. Buat Database

1. Buka phpMyAdmin atau MySQL client
2. Buat database baru sesuai dengan nama yang ada di `config/db.php`
3. Import file SQL jika tersedia di folder `database/` (jika ada)

Atau via command line:

```bash
mysql -u root -p
CREATE DATABASE nama_database;
EXIT;
```

### 3. Konfigurasi Database

Buka file `config/db.php` dan sesuaikan konfigurasi database:

```php
<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "nama_database"; // Sesuaikan dengan database yang dibuat
?>
```

### 4. Jalankan Aplikasi

#### Menggunakan XAMPP:
1. Pindahkan folder project ke `C:\xampp\htdocs\`
2. Start Apache dan MySQL di XAMPP Control Panel
3. Akses di browser: `http://localhost/company-profile`

#### Menggunakan PHP Built-in Server:
```bash
php -S localhost:8000
```
Akses di browser: `http://localhost:8000`

## 🔐 Login Admin

Setelah instalasi, akses admin dashboard di:
```
http://localhost/company-profile/admin
```

## 📁 Struktur Folder

```
company-profile/
├── admin/              # Admin dashboard
├── assets/             # CSS, JS, Images
│   ├── css/
│   ├── js/
│   └── img/
├── config/             # Konfigurasi
│   └── db.php
├── includes/           # File include (header, footer, dll)
├── uploads/            # Upload gambar produk & artikel
├── index.php           # Halaman utama
├── about.php           # Halaman tentang
├── products.php        # Halaman produk
├── articles.php        # Halaman artikel
├── contact.php         # Halaman kontak
└── README.md
```

## 🎯 Penggunaan

### Mengelola Produk
1. Login ke admin dashboard
2. Masuk ke menu "Produk"
3. Klik "Tambah Produk" untuk menambah produk baru
4. Upload gambar, isi deskripsi, dan harga
5. Simpan

### Mengelola Artikel
1. Login ke admin dashboard
2. Masuk ke menu "Artikel"
3. Klik "Tulis Artikel" untuk membuat artikel baru
4. Isi judul, konten, dan upload gambar
5. Publikasikan

### Pemesanan via WhatsApp
- Customer klik tombol "Pesan" pada produk
- Otomatis redirect ke WhatsApp dengan template pesan
- Admin dapat mengatur nomor WhatsApp di dashboard

## 🔧 Kustomisasi

### Mengubah Logo dan Warna
Edit file `assets/css/style.css` untuk menyesuaikan tema warna.

### Mengubah Informasi Perusahaan
Kelola melalui admin dashboard atau edit langsung di database.

### Mengubah Nomor WhatsApp
Atur di admin dashboard → Pengaturan → Kontak

## 🐛 Troubleshooting

### Error Koneksi Database
- Pastikan MySQL sudah running
- Cek konfigurasi di `config/db.php`
- Pastikan database sudah dibuat

### Gambar Tidak Muncul
- Cek permission folder `uploads/`
- Pastikan path gambar sudah benar

### Tidak Bisa Login Admin
- Cek tabel user di database
- Reset password via phpMyAdmin jika perlu

## 📝 Lisensi

Project ini bersifat open source dan bebas digunakan untuk keperluan komersial maupun non-komersial.

## 👨‍💻 Pengembang

Dikembangkan oleh **[eL-hawkeem](https://github.com/eL-hawkeem)**

## 🤝 Kontribusi

Kontribusi selalu diterima! Silakan:
1. Fork repository ini
2. Buat branch baru (`git checkout -b feature/AmazingFeature`)
3. Commit perubahan (`git commit -m 'Add some AmazingFeature'`)
4. Push ke branch (`git push origin feature/AmazingFeature`)
5. Buat Pull Request

## 📧 Kontak

Jika ada pertanyaan atau saran, silakan buat issue di repository ini atau hubungi melalui GitHub.

---

⭐ Jika project ini bermanfaat, jangan lupa berikan bintang!

**Happy Coding!** 🚀
