# SIMPAN

SIMPAN (Sistem Informasi Manajemen Pengumpulan Akademik Network) adalah aplikasi akademik untuk pengelolaan kelas, tugas, pengumpulan, penilaian, dan integritas akademik. Sistem memiliki tiga aktor: admin, pengajar, dan siswa. Rincian kebutuhan produk ada di [SIMPAN_GDD.md](SIMPAN_GDD.md).

## Fitur

- Autentikasi, registrasi siswa, pengelolaan pengguna, dan otorisasi berbasis role.
- Manajemen fakultas, jurusan, kelas, keanggotaan siswa, tugas, pengumpulan ulang, rubrik, penilaian, riwayat nilai, dan disputasi.
- Notifikasi, reminder, diagnosis berbasis provider, voting kelas, portfolio privat/publik, marketplace rubrik, reputasi internal, dan pencatatan transaksi blockchain.
- Integrasi eksternal bersifat opsional. Sepolia dan provider dokumen belum aktif sampai adapter, kontrak/endpoint vendor, persetujuan data, dan kredensial siap.

## Status modul

| Modul | Status saat ini |
|---|---|
| Autentikasi dan role | Implemented; akun tanpa role tidak memperoleh dashboard. |
| Manajemen akademik | Implemented. |
| Tugas dan pengumpulan | Implemented; file berada di private storage. |
| Penilaian dan disputasi | Implemented; riwayat nilai/status tersedia. |
| Blockchain | Outbox dan adapter tersedia; pengiriman Sepolia menunggu signer yang diaudit dan kontrak deployed. |
| IPFS | Adapter tersedia; Pinata menunggu kredensial dan persetujuan publikasi per item. |
| AI dan plagiarism | Adapter tersedia; menunggu endpoint vendor yang telah disesuaikan dan dikonfigurasi. |
| Notifikasi | Alur aplikasi tersedia; penyimpanan/deduplikasi menunggu migration 25. |
| Reminder | Pengingat tenggat tersedia; SLA follow-up nonaktif sampai dikonfigurasi; reminder in-app juga memerlukan migration 25. |
| DAO voting | Voting kelas, satu akun satu suara, hasil setelah tutup tersedia; anchoring blockchain menunggu Sepolia. |
| Portfolio | Fitur lokal privat/publik tersedia; unggah IPFS menunggu Pinata dan consent. |
| Marketplace rubrik | Template, salin, dan verifikasi tersedia. |
| Reputasi | Ledger internal tersedia; penghargaan bernilai nol sampai kebijakan ditetapkan. |

## Teknologi

- Laravel 12, PHP 8.2+, Composer 2.x.
- Blade, Bootstrap 5, JavaScript, Vite, Node.js, dan npm.
- MySQL/MariaDB untuk penggunaan lokal/produksi; SQLite in-memory untuk test.
- PHP extensions: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `mbstring`, `openssl`, `pcre`, `pdo`, `pdo_mysql`, `session`, `tokenizer`, dan `xml`.

## Instalasi

Lihat [INSTALLATION.md](INSTALLATION.md) untuk langkah lengkap. Jika repository telah diterbitkan, clone dari URL resmi project:

```powershell
git clone <URL-REPOSITORY-SIMPAN> simpan
cd simpan
composer install
Copy-Item .env.example .env
php artisan key:generate
npm install
npm run build
```

Jangan menyalin `.env.example` di atas `.env` yang sudah berisi konfigurasi. Buat database `simpan` terlebih dahulu—misalnya lewat phpMyAdmin dengan collation `utf8mb4_unicode_ci`—isi `.env`, lalu tinjau status migration:

```powershell
php artisan migrate:status
```

Pada database kosong untuk instalasi baru, setelah memastikan target koneksi benar, jalankan `php artisan migrate` untuk membuat semua tabel. Saat pemeriksaan terakhir pada database project ini, migration SIMPAN 1–24 berstatus **Ran** dan `2026_11_12_000025_add_idempotency_key_to_notifikasi_table.php` masih **Pending**. Notifikasi belum disimpan sampai dukungan idempotensi tersedia, agar deduplikasi tetap terjamin. Setelah persetujuan, backup, dan pemeriksaan koneksi, terapkan migration tersebut secara terarah:

```powershell
php artisan migrate --path=database/migrations/2026_11_12_000025_add_idempotency_key_to_notifikasi_table.php
```

Jangan gunakan `migrate:fresh`, `migrate:refresh`, atau `migrate:reset` pada database berisi data.

## Bootstrap admin

Admin pertama dibuat melalui command terminal interaktif, bukan registrasi web:

```powershell
php artisan simpan:make-admin
```

Command meminta email dan kata sandi secara interaktif. Untuk akun yang sudah ada, command meminta kata sandi akun dan konfirmasi sebelum menetapkan role admin.

## Menjalankan aplikasi

```powershell
php artisan serve
```

Buka <http://127.0.0.1:8000>. Untuk perubahan frontend saat development, jalankan `npm run dev` pada terminal lain. Untuk build produksi, gunakan `npm run build`.

## Queue dan scheduler

`QUEUE_CONNECTION=sync` dapat dipakai untuk development sederhana. Untuk worker asinkron, atur queue yang sesuai—misalnya `database`—setelah tabel queue tersedia, lalu jalankan:

```powershell
php artisan queue:work --tries=3
php artisan schedule:work
```

Di production, jalankan worker di bawah process supervisor dan jadwalkan `php artisan schedule:run` setiap menit melalui cron atau scheduler platform. Pengingat tindak lanjut penilaian/disputasi nonaktif secara default; atur `SIMPAN_REMINDER_FOLLOWUP_HOURS` setelah SLA ditetapkan. Reminder in-app memerlukan migration 25. Sampai diterapkan, reminder tersebut gagal dengan status `failed`, bukan ditandai terkirim.

## Testing

Konfigurasi PHPUnit memaksa SQLite in-memory agar test suite tidak memakai database dari `.env`:

```powershell
php artisan test
```

Lihat [TESTING.md](TESTING.md). Test integrasi eksternal harus memakai fake adapter dan tidak memerlukan API key.

## Konfigurasi integrasi

Lihat [INTEGRATION_SETUP.md](INTEGRATION_SETUP.md) untuk Pinata, Sepolia, diagnosis AI/plagiarisme, reminder, dan batasan aktif. Jangan commit `.env`, API key, private key, atau dokumen privat.

## Struktur direktori utama

- `app/Contracts`, `app/Services`, `app/Jobs`: kontrak adapter, logika integrasi, dan pekerjaan async.
- `app/Http/Controllers`, `app/Http/Middleware`: HTTP dan otorisasi.
- `app/Models`: Eloquent models.
- `database/migrations`: schema database.
- `resources/views`: Blade views; `resources/js` dan `resources/css`: frontend Vite.
- `routes/web.php`, `routes/console.php`: route web dan jadwal command.
- `contracts`: sumber Solidity; kontrak saat ini belum dikompilasi atau dideploy.
- `tests`: unit dan feature tests.

Panduan production ada di [DEPLOYMENT.md](DEPLOYMENT.md). Project belum dideploy dan memerlukan UAT serta konfigurasi eksternal sebelum integrasi tersebut digunakan.

Untuk demonstrasi lokal dan persiapan data fiktif, lihat [DEMO_GUIDE.md](DEMO_GUIDE.md) dan [DEMO_DATA_SETUP.md](DEMO_DATA_SETUP.md). Skenario manual dan formulir hasil tersedia di [UAT_CHECKLIST.md](UAT_CHECKLIST.md) dan [UAT_REPORT_TEMPLATE.md](UAT_REPORT_TEMPLATE.md); semua hasil UAT awal tetap Belum diuji.
