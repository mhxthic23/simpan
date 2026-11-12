# Testing SIMPAN

## Database isolasi

`phpunit.xml` memaksa `DB_CONNECTION=sqlite` dan `DB_DATABASE=:memory:`. `RefreshDatabase` menjalankan migration pada koneksi sementara; test tidak menyentuh MySQL/MariaDB project maupun data lokalnya.

```powershell
php artisan test
```

Jangan menonaktifkan override database ini atau mengarahkan test ke database operasional. Test eksternal harus menggunakan fake adapter dan tidak membutuhkan API key nyata.

## Cakupan

- Authentication, role, akses antarrole, admin dan manajemen akademik.
- Tugas, upload privat, pengumpulan ulang, rubrik, penilaian, riwayat, dan disputasi.
- Notifikasi, deduplikasi, reminder, dan preferensi.
- Portfolio, persetujuan IPFS, akses privat/publik.
- DAO, kelayakan pemilih, satu suara per akun, dan hasil setelah penutupan.
- Blockchain outbox dan receipt menggunakan fake adapter.
- Diagnosis AI/plagiarisme menggunakan fake provider.
- Marketplace template rubrik dan ledger reputasi.

Filter satu suite misalnya:

```powershell
php artisan test --filter=AssessmentDisputeTest
php artisan test --filter=MilestoneFourTest
```

Jalankan seluruh suite sebelum merge/release. Tinjau semua failure, error, skipped, dan deprecation; jangan menganggap proses berexit code 0 sebagai pengganti pemeriksaan output test.
