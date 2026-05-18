# MeetSpace - Meeting Room Booking

Aplikasi booking ruang meeting profesional berbasis PHP + MySQL untuk Windows/XAMPP.

## Fitur
- Login dan registrasi staff
- Persetujuan akun staff oleh admin
- Dashboard admin dan staff
- Manajemen ruangan lengkap dengan kapasitas, lantai, fasilitas, gambar, dan status
- Pemesanan ruangan oleh staff
- Validasi bentrok jadwal otomatis
- Approval atau rejection booking oleh admin
- Riwayat pemesanan dan profil pengguna

## Kebutuhan Windows
- XAMPP 8.x atau Laragon
- PHP 8.0+
- MySQL/MariaDB
- Browser modern

## Instalasi XAMPP
1. Ekstrak folder `meetspace_app` ke:
   `C:\xampp\htdocs\meetspace_app`
2. Jalankan XAMPP Control Panel.
3. Start `Apache` dan `MySQL`.
4. Buka `http://localhost/phpmyadmin`.
5. Import file:
   `database/meetspace.sql`
6. Buka aplikasi:
   `http://localhost/meetspace_app`

## Akun Demo
- Admin: `admin@meetspace.id` / `admin123`
- Staff: `rina@company.id` / `rina123`

## Konfigurasi Database
Edit file berikut bila username/password MySQL berbeda:
`config/database.php`

Default:
```php
DB_HOST = '127.0.0.1';
DB_NAME = 'meetspace';
DB_USER = 'root';
DB_PASS = '';
```

## Catatan Keamanan
Untuk produksi, disarankan mengganti hash password SHA-256 sederhana menjadi `password_hash()` dan `password_verify()`, mengaktifkan HTTPS, CSRF token, audit log, dan pembatasan rate login.

## Update Dashboard Status Ruangan
Versi ini menambahkan panel **Status Semua Ruangan** di dashboard Admin dan Staff.

Status yang ditampilkan:
- Tersedia: belum ada jadwal approved hari ini.
- Sedang Dipakai: ada booking approved yang sedang berlangsung.
- Dipesan Hari Ini: ada booking approved hari ini, tetapi belum/sudah tidak sedang berlangsung.
- Menunggu Approval: ada request booking pending hari ini.
- Nonaktif: ruangan sedang dinonaktifkan admin.

Panel ini juga menampilkan jumlah booking approved/pending hari ini dan jadwal approved berikutnya.


## Update Fitur Departemen

Versi ini menambahkan:
- Menu Admin: **Master Data Departemen**
- CRUD departemen: tambah, edit, hapus, aktif/nonaktif
- Form **Create User Baru** pada menu Admin > Pengguna
- Field departemen pada data user
- Departemen tampil pada tabel Manajemen Pengguna
- Register staff juga memilih departemen

Jika memakai database lama, import ulang `database/meetspace.sql` atau jalankan migrasi manual berikut:

```sql
CREATE TABLE departments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  code VARCHAR(20) NOT NULL UNIQUE,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

ALTER TABLE users ADD COLUMN department_id INT DEFAULT NULL AFTER status;
ALTER TABLE users ADD CONSTRAINT fk_users_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL;

INSERT INTO departments (name,code,status) VALUES
('Human Resource','HRD','active'),
('Marketing','MKT','active'),
('Finance','FIN','active'),
('Information Technology','IT','active'),
('Operations','OPS','active');
```

## Update fitur: Cancel & Reschedule Admin

Pada menu **Admin > Pemesanan**, admin dapat:
- Menyetujui atau menolak booking.
- **Cancel** booking yang masih pending/approved dengan alasan pembatalan.
- **Reschedule** booking ke ruangan, tanggal, jam, dan jumlah peserta baru.
- Sistem tetap memvalidasi bentrok jadwal dan kapasitas ruangan.

### Catatan upgrade database lama
Jika Anda sudah pernah import database versi sebelumnya, jalankan SQL berikut di phpMyAdmin sebelum memakai fitur cancel/reschedule:

```sql
ALTER TABLE bookings
  MODIFY status ENUM('pending','approved','rejected','canceled') NOT NULL DEFAULT 'pending',
  ADD COLUMN cancellation_reason TEXT NULL AFTER rejection_reason,
  ADD COLUMN rescheduled_by INT NULL AFTER cancellation_reason,
  ADD COLUMN rescheduled_at DATETIME NULL AFTER rescheduled_by,
  ADD CONSTRAINT fk_bookings_rescheduler FOREIGN KEY (rescheduled_by) REFERENCES users(id) ON DELETE SET NULL;
```

Jika database masih baru, cukup import ulang file `database/meetspace.sql`.
# meetspace_app
