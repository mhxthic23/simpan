# Konfigurasi integrasi SIMPAN

Semua secret disimpan hanya pada environment deployment, bukan di repository atau database. Nilai kosong di `.env.example` adalah placeholder. Pada pemeriksaan environment lokal untuk persiapan demo/UAT, credential endpoint integrasi masih kosong.

## Ethereum Sepolia

Variabel: `BLOCKCHAIN_NETWORK=sepolia`, `BLOCKCHAIN_CHAIN_ID=11155111`, `BLOCKCHAIN_RPC_URL`, `BLOCKCHAIN_CONTRACT_ADDRESS`, `BLOCKCHAIN_WALLET_ADDRESS`, `BLOCKCHAIN_PRIVATE_KEY`, `BLOCKCHAIN_EXPLORER_URL`.

Outbox/hash/polling sudah ada. Namun signer transaksi Ethereum belum diimplementasikan atau diaudit dan `contracts/SimpanRegistry.sol` belum dikompilasi/dideploy. Karena itu pengiriman Sepolia belum aktif; mengisi private key saja tidak mengaktifkannya. Sebelum live, implementasikan dan audit signer, deploy/verifikasi kontrak, batasi saldo wallet, pisahkan wallet antar environment, dan uji di Sepolia. Jangan menyimpan data pribadi, nilai mentah, atau dokumen di chain.

## Pinata/IPFS

Variabel: `PINATA_JWT`, `PINATA_API_URL`, `PINATA_GATEWAY_URL`. Siapkan JWT dengan akses minimal pada environment secret manager. Upload dilakukan hanya atas karya yang siswa tandai publik dengan persetujuan eksplisit. File/CID yang sudah dipublikasikan ke IPFS dapat disalin dan tidak dapat dijamin terhapus; jangan unggah berkas privat atau data sensitif.

## AI dan plagiarism provider

Variabel: `GPTZERO_API_KEY`, `SIMPAN_AI_ENDPOINT`, `SIMPAN_AI_PROVIDER_NAME`, `COPYLEAKS_API_KEY`, `SIMPAN_PLAGIARISM_ENDPOINT`, `SIMPAN_PLAGIARISM_PROVIDER_NAME`. Adapter saat ini mengharapkan endpoint multipart dengan bentuk hasil SIMPAN yang dinormalisasi; adapter belum turnkey untuk API vendor/versi tertentu. Implementasikan dan uji kontrak provider sebelum mengisi endpoint produksi. Pemeriksaan meminta persetujuan per pengumpulan. Hasil hanyalah indikasi dan tidak boleh otomatis menentukan nilai atau sanksi.

## Reminder, email, dan reputasi

- Konfigurasikan Laravel Mail jika channel email akan digunakan.
- `SIMPAN_REMINDER_FOLLOWUP_HOURS=0` menonaktifkan pengingat penilaian/disputasi sampai pemilik menentukan SLA.
- `SIMPAN_REPUTATION_SUBMISSION_TOKEN`, `SIMPAN_REPUTATION_GRADE_PUBLISHED_TOKEN`, dan `SIMPAN_REPUTATION_VOTING_TOKEN` bernilai nol secara default. Tetapkan kebijakan poin terlebih dahulu; ledger bukan aset kripto.
- Reminder in-app dengan idempotency memerlukan migration 25; periksa status dan persetujuan sebelum menjalankannya.
