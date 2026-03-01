# SIMAK UNASMAN
## Sistem Informasi Pembayaran Mahasiswa
### Universitas Al-Asyariah Mandar

---

## 📁 Struktur Folder

```
simak/
├── index.php                  # Halaman login
├── logout.php                 # Proses logout
├── database.sql               # Script database MySQL
├── README.md                  # Dokumentasi ini
│
├── config/
│   ├── config.php             # Konfigurasi utama + helper functions
│   └── database.php           # Koneksi database
│
├── includes/
│   ├── header.php             # HTML header + Bootstrap
│   ├── footer.php             # HTML footer + scripts
│   ├── admin_sidebar.php      # Sidebar panel admin
│   └── mhs_sidebar.php        # Sidebar panel mahasiswa
│
├── admin/
│   ├── dashboard.php          # Dashboard admin
│   ├── fakultas.php           # CRUD Fakultas
│   ├── mahasiswa.php          # CRUD Mahasiswa
│   ├── tagihan.php            # Kelola tagihan
│   ├── pembayaran.php         # Riwayat pembayaran
│   └── laporan.php            # Laporan & grafik
│
├── mahasiswa/
│   ├── dashboard.php          # Dashboard mahasiswa
│   ├── tagihan.php            # Lihat & bayar tagihan (Midtrans)
│   ├── payment_finish.php     # Halaman hasil pembayaran
│   ├── riwayat.php            # Riwayat pembayaran
│   └── profil.php             # Edit profil & ganti password
│
└── payment/
    └── notification.php       # Webhook Midtrans (callback)
```

---

## ⚙️ Instalasi

### 1. Persyaratan Sistem
- PHP 8.0+
- MySQL 5.7+ / MariaDB
- Apache/Nginx dengan mod_rewrite
- cURL extension (untuk Midtrans API)

### 2. Setup Database
```sql
-- Jalankan file database.sql di phpMyAdmin atau MySQL CLI:
mysql -u root -p < database.sql
```

### 3. Konfigurasi
Edit file `config/config.php`:
```php
define('APP_URL', 'http://localhost/simak');  // Sesuaikan URL
define('MIDTRANS_SERVER_KEY', 'SB-Mid-server-XXXX');  // Dari dashboard Midtrans
define('MIDTRANS_CLIENT_KEY', 'SB-Mid-client-XXXX');  // Dari dashboard Midtrans
```

Edit file `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
define('DB_NAME', 'simak_unasman');
```

### 4. Konfigurasi Midtrans
1. Daftar akun di https://dashboard.sandbox.midtrans.com
2. Ambil Server Key dan Client Key dari Settings > Access Keys
3. Set Notification URL: `https://yourdomain.com/simak/payment/notification.php`
4. Set Finish/Error/Pending URL ke payment_finish.php

---

## 👤 Akun Demo

| Role | NIM/ID | Password |
|------|--------|----------|
| Admin | ADMIN001 | admin123 |
| Mahasiswa | 2021010001 | mhs123 |
| Mahasiswa | 2021020001 | mhs123 |

---

## 💳 Alur Transaksi

```
1. Admin membuat tagihan untuk mahasiswa
        ↓
2. Mahasiswa login → Lihat tagihan "Belum Bayar"
        ↓
3. Mahasiswa klik "Bayar Sekarang"
        ↓
4. Sistem buat order_id unik & hit Midtrans Snap API
        ↓
5. Bill status → "pending", Payment record dibuat
        ↓
6. Popup Midtrans muncul (pilih metode: VA, QRIS, dll)
        ↓
7. Mahasiswa selesaikan pembayaran di Midtrans
        ↓
8. Midtrans kirim webhook ke /payment/notification.php
        ↓
9. Sistem verifikasi signature & update status
        ↓
10. Bill status → "paid", Payment status → "success"
        ↓
11. Mahasiswa diarahkan ke payment_finish.php (bukti bayar)
```

---

## 🔧 Fitur Utama

### Admin
- ✅ Login dengan role admin
- ✅ CRUD Fakultas
- ✅ CRUD Mahasiswa (dengan auto-create user account)
- ✅ Buat & kelola tagihan
- ✅ Update status tagihan manual
- ✅ Lihat semua riwayat pembayaran
- ✅ Laporan dengan grafik Chart.js

### Mahasiswa
- ✅ Login dengan NIM
- ✅ Dashboard personal
- ✅ Lihat tagihan aktif
- ✅ Bayar via Midtrans Snap (popup)
- ✅ Halaman konfirmasi pembayaran
- ✅ Riwayat transaksi
- ✅ Edit profil & ganti password

---

## 🔒 Keamanan
- Password di-hash dengan `password_hash()` (bcrypt)
- Session-based authentication
- Input sanitization dengan `htmlspecialchars` + `strip_tags`
- Prepared statements (anti SQL injection)
- Verifikasi signature webhook Midtrans
- Role-based access control (Admin/Mahasiswa)

---

## 📦 Teknologi
- **Backend**: PHP 8 Native (no framework)
- **Database**: MySQL
- **Frontend**: Bootstrap 5.3
- **Icons**: Bootstrap Icons
- **Tables**: DataTables
- **Charts**: Chart.js
- **Payment**: Midtrans Snap (Sandbox)
