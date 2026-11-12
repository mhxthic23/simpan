# Panduan Demo SIMPAN

## Gambaran

SIMPAN mendukung alur akademik admin, pengajar, dan siswa: pengaturan fakultas/jurusan/kelas, tugas, pengumpulan berkas privat, rubrik, penilaian, riwayat perubahan nilai, dan disputasi. Tersedia pula modul voting kelas, portfolio, marketplace rubrik, reputasi, diagnosis, reminder, dan pencatatan outbox blockchain. Lihat batas tiap modul pada bagian **Fitur unggulan**; adapter eksternal tidak berarti provider sudah aktif.

## Persiapan sebelum demo

1. Gunakan database demo yang sudah memiliki schema dan data fiktif. Database project yang diperiksa memiliki migration SIMPAN 1–24 berstatus Ran; migration 25 masih Pending. Jangan jalankan migration tanpa persetujuan pemilik.
2. Pastikan MySQL/MariaDB berjalan, `.env` menunjuk database demo yang benar, dan `php artisan migrate:status` menampilkan status sesuai target. Jangan menyalin `.env.example` di atas `.env` yang sudah digunakan.
3. Pastikan storage privat lokal (`storage/app/private`) dapat ditulis oleh PHP. File pengumpulan dan portfolio lokal tidak disajikan melalui public storage.
4. Dari root project, jalankan:

   ```powershell
   npm run build
   php artisan serve --host=127.0.0.1 --port=8000
   ```

   Buka <http://127.0.0.1:8000>. Untuk hot reload saat persiapan, jalankan `npm run dev` di terminal terpisah.
5. Queue saat ini `sync`; proses job berjalan pada request pemicu. Scheduler tidak berjalan otomatis kecuali dijalankan terpisah dengan `php artisan schedule:work`.
6. Siapkan browser/session terpisah untuk setiap role, atau logout setiap kali berpindah akun. Gunakan nama dan isi tugas rekaan, jangan data pribadi atau berkas akademik asli.
7. Siapkan akun sesuai [DEMO_DATA_SETUP.md](DEMO_DATA_SETUP.md). Jangan menaruh password demo permanen di panduan atau source code.

## Alur demo utama

### 1. Admin menyiapkan struktur

Login melalui `/login` menggunakan akun admin demo. Dari dashboard admin:

1. Buat fakultas dan jurusan.
2. Pastikan akun pengajar tersedia di **Pengguna**. Admin dapat membuat akun pengajar dan menetapkan role.
3. Buat kelas untuk jurusan tersebut, tetapkan pengajar, lalu tambahkan akun siswa sebagai anggota kelas.
4. Buka **Audit nilai** untuk menjelaskan bahwa histori perubahan nilai tersedia; detail akan terisi setelah ada penilaian/perubahan.

Role admin dikelola melalui route admin dan middleware; siswa/pengajar tidak memperoleh akses ke halaman ini.

### 2. Pengajar membuat tugas dan rubrik

Logout, lalu login sebagai pengajar. Di `/pengajar/tugas`, buat tugas pada kelas yang ditugaskan kepada akun tersebut. Pilih status terbit, tenggat yang masih akan datang, batas ukuran, dan format yang sesuai dengan berkas demo (misalnya PDF). Tambahkan komponen rubrik dari halaman rubrik tugas.

Pengajar hanya dapat mengelola kelas/tugas yang menjadi tanggung jawabnya.

### 3. Siswa mengumpulkan tugas

Logout, login sebagai siswa anggota kelas, lalu buka `/siswa/tugas`. Pilih tugas yang sudah terbit, unggah PDF fiktif yang sesuai batas, lalu perlihatkan halaman tugas dan berkas pengumpulan. File tersimpan di private storage; pengunduhan dibatasi pada siswa pemilik dan pengajar tugas terkait.

### 4. Pengajar menilai dan mempublikasikan nilai

Login kembali sebagai pengajar, buka pengumpulan tugas, periksa berkas, isi skor setiap komponen rubrik, lalu simpan sebagai **published**. Tunjukkan total nilai yang dihitung aplikasi dan feedback.

### 5. Siswa mengajukan disputasi

Login sebagai siswa. Buka `/siswa/nilai`, periksa nilai yang sudah dipublikasikan, dan ajukan disputasi dengan alasan rekaan yang sopan. Nilai draft dan penilaian siswa lain tidak dapat dibuka.

### 6. Pengajar meninjau dan menyelesaikan

Login sebagai pengajar pemilik kelas. Buka `/pengajar/disputasi`, tandai sedang ditinjau, lalu selesaikan. Jika menerima disputasi, isi skor revisi dan alasan keputusan. Aplikasi menambahkan riwayat perubahan nilai serta riwayat status disputasi.

### 7. Admin memeriksa audit

Login kembali sebagai admin dan buka **Audit nilai**. Tampilkan nilai sebelum/sesudah, pelaku, alasan, dan snapshot histori yang tersedia.

### 8. Modul unggulan yang dapat diperagakan

- **DAO voting:** pengajar kelas membuat draft dengan minimal dua opsi, membuka voting; siswa terdaftar memberikan satu suara. Hasil pilihan ditampilkan setelah voting ditutup. Penutupan otomatis memerlukan scheduler.
- **Portfolio:** siswa membuat karya lokal privat atau publik dan mengelolanya sendiri. Pengajar hanya melihat karya publik dari siswa di kelas tanggung jawabnya.
- **Marketplace rubrik:** pengajar menyimpan template, memublikasikan template, dan menyalin komponennya ke tugas; admin dapat membalik status verifikasi.
- **Reputasi:** halaman ledger menampilkan transaksi yang ada. Pemberian token otomatis bernilai nol sampai kebijakan poin ditetapkan; token bukan aset kripto.
- **Blockchain:** halaman menampilkan catatan outbox/internal dan statusnya. Jangan menyebut transaksi confirmed di Sepolia; signer Ethereum dan kontrak belum siap, serta kredensial kosong.
- **Diagnosis:** halaman dan alur persetujuan tersedia, tetapi provider belum dikonfigurasi. Untuk demo tanpa mengirim berkas keluar, cukup jelaskan halaman dan batasannya; jangan menyetujui pemeriksaan yang tidak diperlukan.
- **Notifikasi/reminder:** navigasi dan pengelolaan preferensi tersedia. Namun migration 25 pending sehingga notifikasi belum tersimpan. Reminder in-app yang jatuh tempo akan gagal dengan status failed; email menggunakan mailer `log`, bukan pengiriman email nyata.
- **IPFS:** adapter tersedia tetapi Pinata belum dikonfigurasi. Jangan memilih upload IPFS pada demo; portfolio lokal tidak memerlukan publikasi eksternal.

## Role dan route yang berguna

| Role | Area utama |
|---|---|
| Admin | `/admin/dashboard`, `/admin/users`, `/admin/fakultas`, `/admin/jurusan`, `/admin/kelas`, `/admin/penilaian/riwayat` |
| Pengajar | `/pengajar/dashboard`, `/pengajar/tugas`, `/pengajar/penilaian`, `/pengajar/disputasi`, `/pengajar/diagnosis`, `/pengajar/voting`, `/pengajar/portfolio` |
| Siswa | `/siswa/dashboard`, `/siswa/tugas`, `/siswa/nilai`, `/siswa/disputasi`, `/siswa/diagnosis`, `/siswa/voting`, `/siswa/portfolio` |

Modul umum yang membutuhkan login: `/blockchain`, `/notifikasi`, `/reminder`, `/rubrik-marketplace`, dan `/reputasi`.

## Batasan yang perlu disampaikan

- Database demo saat pemeriksaan memiliki migration 25 Pending. Ini membuat notifikasi tidak persisten sampai migration disetujui dan diterapkan.
- Tidak ada pengiriman transaksi Sepolia, upload Pinata, AI detection, atau plagiarism check live pada environment saat ini.
- Diagnosis bersifat indikatif dan bukan bukti kecurangan atau dasar otomatis untuk nilai/sanksi.
- Reminder tidak berjalan otomatis tanpa scheduler; email tidak terkirim keluar dengan `MAIL_MAILER=log`.
- Environment lokal memakai `APP_DEBUG=true`; jangan expose server lokal ke internet atau memakai data nyata.

## Troubleshooting demo

| Kendala | Langkah pemeriksaan |
|---|---|
| Login ditolak | Pastikan email/password akun demo benar; akun tanpa role tidak bisa membuka dashboard. Hubungi admin demo untuk role yang tepat. |
| Tugas tidak terlihat | Pastikan tugas berstatus published, kelas sudah memiliki siswa tersebut, dan tenggat belum lewat. |
| Upload gagal | Pastikan format/ukuran sesuai konfigurasi tugas dan `storage/app/private` writable. Gunakan berkas PDF fiktif kecil. |
| Pengajar tidak melihat pengumpulan/disputasi | Pastikan tugas berada pada kelas yang pengajarnya adalah akun login. |
| Nilai tidak terlihat | Pastikan nilai dipublikasikan, bukan draft, dan login sebagai siswa pemilik pengumpulan. |
| Notifikasi kosong | Migration 25 masih pending; notifikasi tidak persisten sampai perubahan schema disetujui. Jangan jalankan migration saat demo tanpa persetujuan. |
| Diagnosis gagal/tidak ada hasil | Endpoint dan API key provider belum tersedia; pemeriksaan tidak menghasilkan skor palsu. Jangan mengirim file keluar hanya untuk demo. |
| Blockchain tidak confirmed | Sepolia signer/contract belum siap; status outbox bukan bukti transaksi jaringan. |
| Assets tidak tampil | Jalankan `npm run build`, lalu refresh browser; untuk development gunakan `npm run dev`. |
| Voting tidak tertutup otomatis | Jalankan scheduler hanya jika memang disiapkan untuk demo; atau jelaskan bahwa penutupan otomatis membutuhkan scheduler. |
