# SIMPAN — General Design Document (GDD)
**Sistem Informasi Manajemen Pengumpulan Akademik Network**
Blockchain-based • Transparan • Orisinal • Terstruktur • AI Personal Reminder

| Item | Isi |
|---|---|
| Metode | DDR + SDLC Waterfall termodifikasi + Sprint (Scrum ringan) |
| Durasi | 6 minggu |
| Luaran | Aplikasi web teruji + buku panduan + video dokumentasi |
---
## 1. Tujuan Produk
1. Pengumpulan tugas terintegrasi (siswa, pengajar, admin).
2. Cek orisinalitas mandiri + deteksi AI-generated content.
3. Penilaian transparan via rubrik + smart contract, tercatat permanen di blockchain.
4. Disputasi nilai dan voting kelas (DAO) yang bisa diaudit.
5. Portofolio digital terverifikasi.
6. AI Personal Reminder agar siswa disiplin mengerjakan tugas.
## 2. Keputusan Teknis (Rekomendasi)
> ⚠️ Proposal punya **inkonsistensi stack**: Bab 2.1 menyebut React + Node/Express + Hyperledger/Ethereum, sedangkan Tabel 2.6 dan implementasi menyebut **Laravel 12 + Bootstrap 5 + MySQL**. Kesimpulan menyebut Hyperledger Fabric + IPFS. **Disarankan menyeragamkan** sebelum dikumpulkan.
| Lapisan | Pilihan (sesuai prototype) | Catatan |
|---|---|---|
| Backend | Laravel 12, PHP 8.x | Auth, queue, scheduler, REST |
| Frontend | Blade + Bootstrap 5 + JS | Tema gelap sesuai mockup |
| Database | MySQL | 17 tabel (lihat §5) |
| Blockchain | **Ethereum testnet (Sepolia) + Solidity** untuk MVP | Hyperledger Fabric = target produksi (butuh infrastruktur berat) |
| Penyimpanan file | IPFS via Pinata | Simpan `hash_file_ipfs` di DB |
| Plagiarisme | TF-IDF internal + API Copyleaks | Internal untuk lintas-waktu antar tugas |
| Deteksi AI | API GPTZero / detektor sejenis | Fine-tune = tahap lanjut |
| Reminder AI | Laravel Scheduler + Queue + LLM API | Email, WhatsApp opsional |
| Tools | Git/GitHub, VS Code, Draw.io, Composer | |
| Testing | PHPUnit, Black Box, UAT, JMeter | |
## 3. Aktor & Fitur
**Siswa:** SSO/login • dashboard • upload tugas (PDF/DOCX/PPT) • self-diagnostic plagiarisme + heatmap + saran parafrase • riwayat • status nilai • verifikasi nilai di blockchain • ajukan/lacak disputasi • voting DAO • portofolio • notifikasi.
**Pengajar:** buat kelas/tugas/rubrik • impor rubrik marketplace • kelola siswa • cek plagiarisme & AI • AI Writing Profile • nilai manual/otomatis (smart contract) • preview & publikasi nilai • kelola disputasi/mediasi • buat voting • analitik kelas.
**Admin:** manajemen & verifikasi user • audit trail blockchain • verifikasi transaksi • disputasi level institusi • laporan integritas • kurasi marketplace • konfigurasi (threshold plagiarisme) • monitoring.
### Prioritas Rilis
| Tier | Fitur |
|---|---|
| **MVP (Must)** | Auth+role, kelas, tugas, rubrik, upload ke IPFS, penilaian + hash ke blockchain, publikasi nilai, disputasi dasar, blockchain log, dashboard 3 role |
| **Should** | Self-diagnostic + heatmap, deteksi AI, AI Writing Profile, voting DAO, portofolio, notifikasi, AI Reminder |
| **Could** | Rubrik marketplace, saran parafrase, analitik kelas, token reputasi, laporan akademik |
## 4. Arsitektur Sistem
```
Browser ──HTTP──► Laravel (Controller → Service → Model)
                    ├─► MySQL           (data utama)
                    ├─► IPFS/Pinata     (file tugas → CID)
                    ├─► Blockchain      (hash nilai, voting, disputasi)
                    ├─► Copyleaks/GPTZero (plagiarisme & AI)
                    └─► Queue + Scheduler (cek async, reminder, notifikasi)
```
**Prinsip:** data besar/sensitif tetap di MySQL & IPFS; blockchain hanya menyimpan **hash + timestamp + tipe transaksi** (hemat biaya, privasi aman).
**Modul Laravel:** `Auth` • `Class` • `Assignment` • `Submission` • `Plagiarism` • `AIProfile` • `Grading` • `Dispute` • `Voting` • `Blockchain` • `Portfolio` • `Reminder` • `Notification` • `Admin`
## 5. Model Data (17 Entitas)
`users` → `fakultas` → `jurusan` → `kelas` ↔ `kelas_siswa` → `tugas` → `rubrik` ; `rubrik_marketplace` ; `pengumpulan_tugas` (hash_file_ipfs, similarity_score, ai_score) ; `self_diagnostic_log` ; `penilaian` ; `blockchain_transaction` ; `disputasi` ; `voting_dao` ; `voting_log` ; `token_reputasi` ; `notifikasi`.
**Relasi inti:** kelas 1–N tugas • tugas 1–N rubrik • tugas 1–N pengumpulan • pengumpulan 1–1 penilaian • penilaian 0..1 disputasi • semua aksi penting → `blockchain_transaction`.
**Tambahan disarankan:** tabel `reminder` (user_id, tugas_id, jadwal, channel, status, isi_pesan) karena AI Reminder belum ada di ERD proposal.
## 6. Alur Sistem Utama
### A. Pengumpulan Tugas
1. Pengajar buat tugas + rubrik + deadline.
2. Siswa (opsional) jalankan **Self-Diagnostic** → skor + heatmap → revisi.
3. Siswa upload → validasi format/ukuran → simpan ke IPFS (CID).
4. Sistem hitung hash file → simpan `pengumpulan_tugas`.
5. Job async: cek plagiarisme (TF-IDF + Copyleaks) + skor AI.
6. Catat transaksi `SUBMISSION` ke blockchain (hash + waktu).
7. Notifikasi ke siswa & pengajar.
### B. Penilaian Transparan
1. Pengajar lihat laporan plagiarisme/AI + AI Writing Profile.
2. Isi nilai per komponen rubrik (manual) atau jalankan **smart contract** (hitung bobot otomatis).
3. **Preview** hasil → **Publikasi**.
4. Hash penilaian ditulis ke blockchain (`GRADE`).
5. Siswa dapat verifikasi nilai lewat blockchain explorer/halaman log.
### C. Disputasi Nilai
Siswa ajukan (alasan + bukti) → pengajar tanggapi → jika belum selesai, mediasi/admin institusi → keputusan → hasil dicatat (`DISPUTE`). Status dapat dilacak tiap tahap.
### D. DAO Kelas (Voting)
Pengajar buat usulan (opsi, deadline) → siswa vote (1 suara/token reputasi) → vote dicatat di `voting_log` + hash on-chain → setelah deadline, hasil dihitung otomatis oleh smart contract.
### E. AI Personal Reminder
Scheduler harian membaca tugas aktif & status siswa → tentukan pemicu (H-3, H-1, 3 jam sebelum deadline, tugas belum dibuka) → LLM menyusun pesan personal (nada suportif, bukan menghakimi) → kirim via in-app/email/WhatsApp → catat di `reminder` & `notifikasi`. Siswa bisa atur frekuensi/opt-out.
### F. Portofolio
Tugas bernilai final + tercatat blockchain otomatis masuk portofolio → halaman publik/tautan verifikasi untuk calon pemberi kerja.
## 7. Alur Pengembangan (6 Minggu)
| Minggu | Fokus | Output |
|---|---|---|
| 1 | Analisis & perencanaan: kuesioner/FGD, analisis Turnitin/Classroom/Moodle, Product Backlog | Backlog, kebutuhan final |
| 2 | Proposal + desain: arsitektur, ERD, DFD, wireframe/mockup | Dokumen desain, prototipe UI |
| 3 | Sprint 1: setup Laravel, migrasi DB, auth+role, kelas/tugas/rubrik | Fondasi berjalan |
| 4 | Sprint 2: upload+IPFS, plagiarisme/AI check, penilaian, integrasi blockchain | Fitur inti end-to-end |
| 5 | Sprint 3: disputasi, voting DAO, portofolio, reminder, notifikasi, log | Fitur lanjutan |
| 6 | Pengujian (Black Box + UAT), perbaikan, buku panduan, video | Rilis final |

**Per-sprint:** Planning → Develop → Review → Retrospective. Branch: `main` / `develop` / `feature/*`.
## 8. Pengujian & Validasi
- **Unit & Integrasi:** PHPUnit; uji kontrak (Hardhat/Remix) + integrasi Laravel↔blockchain.
- **Performa:** JMeter, target 1000 user simultan, halaman < 3 dtk, upload < 5 dtk.
- **Keamanan:** validasi upload, CSRF/XSS, hak akses per role, audit smart contract.
- **Black Box:** input–output tiap modul vs kebutuhan fungsional.
- **UAT (Ahli media, Guru, Siswa):** angket skala 1–4 pada usefulness, usability, user satisfaction.
- **Rumus:** `Persentase = (Skor diperoleh / Skor maksimum) × 100%` → 81–100% = 4 (sangat baik), 61–80% = 3, 41–60% = 2, 0–40% = 1.
## 9. Kebutuhan Non-Fungsional
Keamanan data • uptime ≥ 99% • respons < 3 dtk • 1000 user simultan • responsif lintas perangkat/browser • UI mudah dipahami • integritas data via blockchain.
## 10. Risiko & Mitigasi
| Risiko | Mitigasi |
|---|---|
| Blockchain terlalu kompleks untuk 6 minggu | MVP di testnet, hanya simpan hash |
| Biaya/limit API Copyleaks & GPTZero | Cache hasil, TF-IDF internal sebagai cadangan, batasi cek per hari |
| Akurasi deteksi AI (false positive) | Skor hanya indikator; keputusan tetap di pengajar + jalur disputasi |
| Data pribadi on-chain | Hanya hash, tanpa data identitas mentah |
| Hosting prototype gratis (InfinityFree) terbatas | Siapkan VPS/hosting lain untuk demo & uji beban |
| Stack di proposal tidak konsisten | Putuskan satu stack di Minggu 1 |
## 11. Definition of Done
- [ ] Semua fitur Must + Should berjalan di 3 role
- [ ] Setiap submit, nilai, disputasi, voting tercatat di blockchain & terverifikasi
- [ ] Lolos Black Box tanpa bug kritis
- [ ] UAT dilakukan, hasil validasi terisi (Tabel 6.5)
- [ ] Buku panduan + video dokumentasi selesai
- [ ] Proposal diselaraskan (stack, nomor tabel/gambar, template pengesahan)
## 12. Catatan Perbaikan Proposal
- Tujuan nomor 6 kosong; penomoran 1.2/1.3 di daftar isi tidak cocok isi.
- Nomor gambar "2.3" dipakai berulang (ERD, DFD, login); nomor tabel "6.x"/"3.8" tidak berurutan.
- Gambar ERD memuat tabel ganda dan teks rusak; sebaiknya digambar ulang di Draw.io.
- Kesimpulan menyebut "hasil uji coba" padahal pengujian belum dilakukan; ubah ke rencana/hipotesis atau isi setelah UAT.
- Tahun di lembar pengesahan (2025) berbeda dengan sampul (Februari 2026); "remainder" → **reminder**.