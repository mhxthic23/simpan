# Menyiapkan Data Demo SIMPAN

Data demo disiapkan manual pada database demo yang sudah memiliki schema. Tidak ada DemoSeeder pada tahap ini: seeder yang membuat role akun dan relasi akademik berisiko memasukkan password, menimpa akun yang ada, atau menulis ke database yang salah. Langkah manual memberi operator kendali dan tidak memerlukan perubahan schema.

## Prinsip keamanan

- Gunakan database terpisah khusus demo/test; jangan memakai akun atau data akademik produksi.
- Pakai nama, email domain reserved seperti `example.test`, tugas, alasan disputasi, serta berkas sintetis.
- Jangan catat password pada source code, dokumentasi, tiket, atau hasil test. Bagikan kredensial sementara kepada penguji melalui saluran terpisah, lalu ganti atau nonaktifkan setelah demo.
- Jangan menaruh berkas demo pada public storage. Gunakan upload melalui aplikasi agar tersimpan pada private disk.
- Jangan mengaktifkan Pinata, AI, plagiarism, atau Sepolia demi demo tanpa persetujuan atas pengiriman data dan konfigurasi yang aman.
- Jangan menjalankan migration 25 tanpa persetujuan pemilik. Periksa `php artisan migrate:status` dan pastikan database yang dipilih benar.

## Akun dan role

Siapkan:

| Akun | Cara menyiapkan | Catatan |
|---|---|---|
| Admin | Gunakan admin demo yang sudah disediakan pemilik. Jika belum ada, bootstrap melalui `php artisan simpan:make-admin` setelah database demo dipastikan benar. | Command terminal interaktif; jangan gunakan registrasi publik untuk admin. |
| Pengajar | Admin membuat akun dengan role `pengajar` pada **Pengguna**. | Berikan password sementara di luar dokumentasi dan minta pengajar menggantinya. |
| Siswa | Registrasi publik membuat role `siswa`, atau admin membuat akun siswa. | Gunakan data fiktif; role dari form publik tidak dipercaya. |

Command bootstrap admin tidak mengubah password akun yang dipromosikan; untuk pengguna baru, simpan kredensial sementara dengan aman.

## Struktur data contoh

Gunakan label fiktif berikut atau nama lain yang tidak mengidentifikasi orang nyata:

- Fakultas: `Fakultas Contoh Demo`.
- Jurusan: `Sistem Informasi Demo` di fakultas tersebut.
- Kelas: `SI-Demo-01`, diajar oleh akun pengajar demo.
- Siswa: satu atau dua akun demo yang terdaftar ke kelas melalui admin.
- Tugas: `Analisis Kasus Akademik (Data Sintetis)`, terbit, tenggat setelah waktu demo, maksimal 2 MB, format PDF.
- Rubrik: `Analisis` (40%, maksimum 10) dan `Bukti` (60%, maksimum 10).
- Pengumpulan: satu file PDF sintetis dengan teks semacam “Berkas simulasi UAT SIMPAN” tanpa data asli.
- Penilaian: isi skor sesuai komponen; publikasi melalui alur pengajar.
- Disputasi: alasan fiktif seperti `Mohon tinjau kembali penilaian komponen bukti.`

## Urutan menyiapkan data

1. Pastikan server DB lokal/demo berjalan. Verifikasi host, port, dan nama database di `.env` tanpa menampilkan password; periksa migration dengan `php artisan migrate:status`.
2. Login admin, buat fakultas dan jurusan, lalu buat akun pengajar.
3. Buat kelas pada jurusan, tetapkan pengajar, buat akun siswa, dan tambahkan siswa ke kelas.
4. Login pengajar, buat tugas dengan tenggat di masa depan, dan simpan rubrik.
5. Login siswa, unggah PDF sintetis sebelum tenggat.
6. Login pengajar, publikasikan penilaian. Login siswa untuk melihat nilai dan mengajukan disputasi.
7. Login pengajar untuk meninjau dan menyelesaikan disputasi. Login admin untuk melihat riwayat nilai.
8. Tambahkan data voting/portfolio/marketplace hanya jika modul tersebut akan diperagakan; jangan membuat voting aktif dengan periode yang sudah lewat.

## Data fitur unggulan

- **DAO:** buat proposal menggunakan kelas yang telah memiliki siswa dan minimal dua opsi rekaan. Pastikan periode dimulai dan berakhir setelah demo. Jangan memilih siswa sebagai akun yang sama dengan pengajar.
- **Portfolio:** gunakan teks/file sintetis. Untuk demonstrasi aman, biarkan portfolio lokal; jangan meminta `publish_to_ipfs` karena Pinata belum diaktifkan.
- **Marketplace:** pengajar membuat draft template rubrik dengan total bobot 100%, lalu dapat memublikasikannya dan menyalin ke tugas demo yang belum memiliki rubrik. Admin dapat menunjukkan toggle verifikasi.
- **Reputasi:** token otomatis disetel nol. Jangan mengubah config untuk membuat saldo demo tanpa kebijakan yang disepakati; cukup jelaskan ledger dan tampilan empty state.
- **Blockchain:** aktivitas akademik membuat outbox/hash internal; ini bukan transaksi Sepolia. Jangan mengedit status transaksi untuk membuatnya tampak confirmed.
- **Diagnosis, notifikasi, dan reminder:** provider eksternal kosong dan migration notifikasi 25 pending. Jangan memalsukan hasil atau status; tunjukkan batasan melalui panduan demo.

## Setelah demo

Logout semua sesi, ganti/nonaktifkan akun sementara, hapus file/data demo hanya melalui kebijakan operator database demo, dan simpan catatan UAT. Jangan jalankan penghapusan terhadap database project atau data pengguna nyata.
