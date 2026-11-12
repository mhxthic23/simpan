# Instalasi SIMPAN

## Prasyarat

- PHP 8.2 atau lebih baru dengan extension Laravel yang tercantum di README, termasuk `pdo_mysql` untuk MySQL/MariaDB.
- Composer 2.x, Node.js/npm, MySQL atau MariaDB.
- Akses ke repository project. Ganti `<URL-REPOSITORY-SIMPAN>` dengan URL yang diberikan pemilik project.

## Clone dan dependencies

```powershell
git clone <URL-REPOSITORY-SIMPAN> simpan
cd simpan
composer install
npm install
```

Pada workspace yang sudah memiliki project, lewati `git clone` dan masuk ke folder root project.

## Konfigurasi environment

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

Jika `.env` sudah ada, jangan menimpanya. Atur `APP_ENV=local`, `APP_DEBUG=true`, `APP_URL`, serta `DB_CONNECTION=mysql`, host, port, nama database, username, dan password yang sesuai environment setempat. Jangan bagikan isi `.env`.

Buat database kosong bernama `simpan` melalui MySQL/MariaDB atau phpMyAdmin. Di phpMyAdmin pilih **New**, isi nama `simpan`, pilih collation `utf8mb4_unicode_ci`, lalu pilih **Create**. Atur `.env` agar host/port/user sesuai server tersebut. Periksa koneksi dan migration terlebih dahulu:

```powershell
php artisan migrate:status
```

Migration 25 menambah kolom `idempotency_key` nullable dengan unique index pada `notifikasi`. Baris lama mendapat nilai `NULL`; MariaDB/MySQL mengizinkan beberapa NULL pada unique index sehingga data yang sudah ada tidak bertabrakan. Dalam pemeriksaan terakhir, tabel notifikasi berisi 0 baris. Migration tidak menghapus atau menulis ulang data, tetapi alter table dapat mengambil metadata/table lock bergantung versi dan ukuran tabel. Pastikan backup dan maintenance window sesuai skala deployment.

Untuk database baru yang dipastikan kosong, tinjau kembali target koneksi dan terapkan seluruh schema:

```powershell
php artisan migrate
```

Pada database SIMPAN yang sudah berisi schema, migration 25 masih memerlukan persetujuan pemilik dan backup. Setelah disetujui, terapkan hanya migration pending itu:

```powershell
php artisan migrate --path=database/migrations/2026_11_12_000025_add_idempotency_key_to_notifikasi_table.php
```

Perintah path membatasi penerapan pada database SIMPAN saat ini ke satu migration pending. Jangan jalankan perintah reset database.

## Assets, admin, dan server lokal

```powershell
npm run build
php artisan simpan:make-admin
php artisan serve
```

`simpan:make-admin` berjalan di terminal dan tidak tersedia sebagai endpoint web. Jika menggunakan database project yang berisi data, jangan membuat admin atau migration sebelum memastikan target database dan mendapatkan persetujuan pemilik.

Untuk hot reload frontend, jalankan `npm run dev` di terminal lain. Untuk scheduler lokal:

```powershell
php artisan schedule:work
```

## Sesudah instalasi

- Konfigurasikan mail, queue, dan scheduler sesuai kebutuhan.
- Uji admin, pengajar, dan siswa menggunakan akun test yang disetujui.
- Jalankan test suite yang secara paksa menggunakan SQLite in-memory: `php artisan test`.
- Lihat [INTEGRATION_SETUP.md](INTEGRATION_SETUP.md) sebelum mengisi secret integrasi.
