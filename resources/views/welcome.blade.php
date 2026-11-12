<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f4f7fb">
    <title>{{ config('app.name', 'SIMPAN') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="container py-5">
        <div class="row justify-content-center align-items-center min-vh-100 py-5">
            <div class="col-12 col-lg-8 col-xl-7">
                <section class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4 p-md-5">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <span class="brand-mark">S</span>
                            <div>
                                <p class="text-primary fw-semibold small text-uppercase mb-1">Sistem Informasi Akademik</p>
                                <h1 class="h3 mb-0">SIMPAN</h1>
                            </div>
                        </div>

                        <p class="lead mb-4">Sistem Informasi Manajemen Pengumpulan Akademik Network</p>
                        <p class="text-secondary mb-4">Fondasi aplikasi berhasil disiapkan. Tahap berikutnya dapat membangun alur kelas, tugas, pengumpulan, dan penilaian sesuai GDD.</p>

                        <div class="d-flex flex-wrap gap-2 mb-4" aria-label="Teknologi aplikasi">
                            <span class="badge text-bg-light border">Laravel 12</span>
                            <span class="badge text-bg-light border">PHP 8.x</span>
                            <span class="badge text-bg-light border">Blade</span>
                            <span class="badge text-bg-light border">Bootstrap 5</span>
                            <span class="badge text-bg-light border">MySQL</span>
                        </div>
                    </div>
                </section>
                <p class="text-center text-secondary small mt-3 mb-0">Project initialization · {{ date('Y') }}</p>
            </div>
        </div>
    </main>
</body>
</html>
