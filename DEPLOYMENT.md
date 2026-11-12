# Panduan deployment SIMPAN

Panduan ini menyiapkan deployment; tidak ada deployment yang dilakukan. Sebelum go-live, selesaikan UAT, review operasional, backup, serta integrasi yang memang akan diaktifkan.

## Infrastruktur yang disarankan

Untuk tim kecil hingga menengah, gunakan VPS terkelola atau lingkungan cloud setara dengan Nginx, PHP-FPM pada versi PHP yang didukung Laravel 12, MySQL/MariaDB terkelola atau instance database terpisah, process supervisor untuk worker, cron untuk scheduler, dan persistent private storage. Pisahkan aplikasi, database, dan backup; ukur beban sebelum memilih ukuran instance. Jika penggunaan bertumbuh, pindahkan file privat ke object storage privat dengan kebijakan akses dan retensi.

## Checklist release

1. Deploy source melalui pipeline; pasang dependency production dengan `composer install --no-dev --optimize-autoloader`; build frontend dengan `npm ci` dan `npm run build` pada build stage.
2. Isi secret di secret manager: `APP_KEY`, konfigurasi database, mail, dan hanya integrasi yang sudah disetujui. Set `APP_ENV=production`, `APP_DEBUG=false`, serta `APP_URL` HTTPS yang benar. Jangan mengunggah `.env` ke repository.
3. Pastikan document root web menunjuk ke `public/`. Jangan expose project root, `.env`, source upload, atau direktori private storage.
4. Atur permission agar PHP dapat menulis `storage/` dan `bootstrap/cache/`, dengan hak minimum yang diperlukan. File submission dan portfolio privat tetap di disk non-public dan diunduh melalui controller berotorisasi.
5. Backup database dan file privat sebelum perubahan schema. Periksa `php artisan migrate:status`; jalankan hanya migration yang disetujui. Jangan menggunakan perintah reset di production.
6. Setelah konfigurasi lengkap, jalankan `php artisan config:cache`, `php artisan route:cache`, dan `php artisan view:cache`. Bersihkan/rebuild cache saat konfigurasi berubah.
7. Jalankan queue worker di bawah Supervisor/systemd/platform process manager dan siapkan restart worker setiap release. Jalankan scheduler tiap menit (`php artisan schedule:run`) serta monitor proses yang gagal.
8. Gunakan session/cache storage yang sesuai deployment multi-instance. Konfigurasikan HTTPS, secure cookies, rate limiting, firewall, log rotation, alerting, dan pemantauan kapasitas.
9. Uji login per role, upload/download privat, notifikasi, scheduler, backup/restore, dan prosedur rollback pada staging sebelum menerima trafik.

## Backup dan recovery

Buat backup terenkripsi database secara terjadwal, salinan file privat, retensi yang terdokumentasi, dan uji pemulihan secara berkala. Simpan backup terpisah dari host aplikasi dengan akses terbatas. Simpan log tanpa password, API key, private key, isi dokumen, atau payload sensitif.

## Batas kesiapan

Belum dideploy atau diuji pada staging/production. Sepolia belum bisa mengirim transaksi karena signer dan kontrak belum siap; Pinata dan provider diagnosis memerlukan credential serta validasi vendor; migration 25 masih pending; queue lokal saat ini memakai `sync`. Tandai release sebagai siap UAT/demo setelah konfigurasi environment dan persetujuan migration selesai, bukan sebagai production-ready sebelum checklist operasional terpenuhi.
