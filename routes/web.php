<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\FakultasController;
use App\Http\Controllers\Admin\JurusanController;
use App\Http\Controllers\Admin\KelasController;
use App\Http\Controllers\Admin\AuditPenilaianController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BlockchainController;
use App\Http\Controllers\ReputationController;
use App\Http\Controllers\RubrikMarketplaceController;
use App\Http\Controllers\VotingController;
use App\Http\Controllers\DiagnosticController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\ReminderPreferencesController;
use App\Http\Controllers\Siswa\PortfolioController as StudentPortfolioController;
use App\Http\Controllers\Pengajar\PortfolioSiswaController;
use App\Http\Controllers\Pengajar\PengumpulanController as PengajarPengumpulanController;
use App\Http\Controllers\Pengajar\DisputasiController as PengajarDisputasiController;
use App\Http\Controllers\Pengajar\PenilaianController as PengajarPenilaianController;
use App\Http\Controllers\Pengajar\RubrikController as PengajarRubrikController;
use App\Http\Controllers\Pengajar\TugasController as PengajarTugasController;
use App\Http\Controllers\Siswa\DisputasiController as SiswaDisputasiController;
use App\Http\Controllers\Siswa\NilaiController as SiswaNilaiController;
use App\Http\Controllers\Siswa\PengumpulanController as SiswaPengumpulanController;
use App\Http\Controllers\Siswa\TugasController as SiswaTugasController;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegistration'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/notifikasi', [NotifikasiController::class, 'index'])->name('notifikasi.index');
    Route::patch('/notifikasi/{notifikasi}/read', [NotifikasiController::class, 'read'])->name('notifikasi.read');
    Route::patch('/notifikasi/read-all', [NotifikasiController::class, 'readAll'])->name('notifikasi.read-all');
    Route::get('/reminder', [ReminderController::class, 'index'])->name('reminder.index');
    Route::patch('/reminder/{reminder}/cancel', [ReminderController::class, 'cancel'])->name('reminder.cancel');
    Route::get('/reminder/preferences', [ReminderPreferencesController::class, 'edit'])->name('reminder.preferences.edit');
    Route::put('/reminder/preferences', [ReminderPreferencesController::class, 'update'])->name('reminder.preferences.update');
    Route::get('/blockchain', [BlockchainController::class, 'index'])->name('blockchain.index');
    Route::get('/blockchain/{transaction}', [BlockchainController::class, 'show'])->name('blockchain.show');
    Route::get('/portfolio/{portfolio}/file', [StudentPortfolioController::class, 'download'])->name('portfolio.download');
    Route::get('/rubrik-marketplace', [RubrikMarketplaceController::class, 'index'])->name('marketplace.index');
    Route::get('/rubrik-marketplace/{rubrikMarketplace}', [RubrikMarketplaceController::class, 'show'])->whereNumber('rubrikMarketplace')->name('marketplace.show');
    Route::get('/reputasi', [ReputationController::class, 'index'])->name('reputation.index');

    Route::prefix('siswa')->name('siswa.')->middleware('role:siswa')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'siswa'])->name('dashboard');
        Route::get('/tugas', [SiswaTugasController::class, 'index'])->name('tugas.index');
        Route::get('/tugas/{tugas}', [SiswaTugasController::class, 'show'])->name('tugas.show');
        Route::post('/tugas/{tugas}/pengumpulan', [SiswaPengumpulanController::class, 'store'])->name('tugas.pengumpulan.store');
        Route::get('/pengumpulan/{submission}/file', [SiswaPengumpulanController::class, 'download'])->name('pengumpulan.download');
        Route::get('/nilai', [SiswaNilaiController::class, 'index'])->name('nilai.index');
        Route::get('/nilai/{penilaian}', [SiswaNilaiController::class, 'show'])->name('nilai.show');
        Route::post('/nilai/{penilaian}/disputasi', [SiswaDisputasiController::class, 'store'])->name('nilai.disputasi.store');
        Route::get('/disputasi', [SiswaDisputasiController::class, 'index'])->name('disputasi.index');
        Route::get('/voting', [VotingController::class, 'index'])->name('voting.index');
        Route::get('/voting/{votingDao}', [VotingController::class, 'show'])->name('voting.show');
        Route::post('/voting/{votingDao}/vote', [VotingController::class, 'vote'])->name('voting.vote');
        Route::get('/diagnosis', [DiagnosticController::class, 'studentIndex'])->name('diagnosis.index');
        Route::post('/pengumpulan/{submission}/diagnosis', [DiagnosticController::class, 'run'])->name('diagnosis.run');
        Route::resource('portfolio', StudentPortfolioController::class)->except('destroy');
        Route::delete('/portfolio/{portfolio}', [StudentPortfolioController::class, 'destroy'])->name('portfolio.destroy');
    });

    Route::prefix('pengajar')->name('pengajar.')->middleware('role:pengajar')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'pengajar'])->name('dashboard');
        Route::resource('tugas', PengajarTugasController::class)->parameters(['tugas' => 'tugas']);
        Route::get('/tugas/{tugas}/pengumpulan', [PengajarPengumpulanController::class, 'index'])->name('tugas.pengumpulan.index');
        Route::get('/tugas/{tugas}/belum-mengumpulkan', [PengajarPengumpulanController::class, 'missing'])->name('tugas.missing');
        Route::get('/tugas/{tugas}/pengumpulan/{submission}', [PengajarPengumpulanController::class, 'show'])->name('tugas.pengumpulan.show');
        Route::get('/pengumpulan/{submission}/file', [PengajarPengumpulanController::class, 'download'])->name('pengumpulan.download');
        Route::get('/penilaian', [PengajarPenilaianController::class, 'index'])->name('penilaian.index');
        Route::get('/tugas/{tugas}/pengumpulan/{submission}/nilai', [PengajarPenilaianController::class, 'edit'])->name('penilaian.edit');
        Route::post('/tugas/{tugas}/pengumpulan/{submission}/nilai', [PengajarPenilaianController::class, 'save'])->name('penilaian.save');
        Route::get('/tugas/{tugas}/rubrik', [PengajarRubrikController::class, 'index'])->name('tugas.rubrik.index');
        Route::post('/tugas/{tugas}/rubrik', [PengajarRubrikController::class, 'store'])->name('tugas.rubrik.store');
        Route::put('/tugas/{tugas}/rubrik', [PengajarRubrikController::class, 'update'])->name('tugas.rubrik.update');
        Route::delete('/tugas/{tugas}/rubrik/{rubrik}', [PengajarRubrikController::class, 'destroy'])->name('tugas.rubrik.destroy');
        Route::get('/disputasi', [PengajarDisputasiController::class, 'index'])->name('disputasi.index');
        Route::get('/disputasi/{disputasi}', [PengajarDisputasiController::class, 'show'])->name('disputasi.show');
        Route::get('/portfolio', [PortfolioSiswaController::class, 'index'])->name('portfolio.students.index');
        Route::get('/portfolio/{siswa}', [PortfolioSiswaController::class, 'show'])->name('portfolio.student.show');
        Route::get('/voting', [VotingController::class, 'index'])->name('voting.index');
        Route::get('/voting/create', [VotingController::class, 'create'])->name('voting.create');
        Route::post('/voting', [VotingController::class, 'store'])->name('voting.store');
        Route::get('/voting/{votingDao}', [VotingController::class, 'show'])->name('voting.show');
        Route::get('/voting/{votingDao}/edit', [VotingController::class, 'edit'])->name('voting.edit');
        Route::put('/voting/{votingDao}', [VotingController::class, 'update'])->name('voting.update');
        Route::post('/voting/{votingDao}/publish', [VotingController::class, 'publish'])->name('voting.publish');
        Route::get('/diagnosis', [DiagnosticController::class, 'teacherIndex'])->name('diagnosis.index');
        Route::get('/diagnosis/{log}', [DiagnosticController::class, 'teacherShow'])->name('diagnosis.show');
        Route::get('/rubrik-marketplace/create', [RubrikMarketplaceController::class, 'create'])->name('marketplace.create');
        Route::post('/rubrik-marketplace', [RubrikMarketplaceController::class, 'store'])->name('marketplace.store');
        Route::post('/rubrik-marketplace/{rubrikMarketplace}/publish', [RubrikMarketplaceController::class, 'publish'])->name('marketplace.publish');
        Route::post('/rubrik-marketplace/{rubrikMarketplace}/copy', [RubrikMarketplaceController::class, 'copy'])->name('marketplace.copy');
        Route::post('/disputasi/{disputasi}/review', [PengajarDisputasiController::class, 'review'])->name('disputasi.review');
        Route::post('/disputasi/{disputasi}/resolve', [PengajarDisputasiController::class, 'resolve'])->name('disputasi.resolve');
    });

    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'admin'])->name('dashboard');
        Route::post('/blockchain/{transaction}/retry', [\App\Http\Controllers\Admin\BlockchainRetryController::class, 'retry'])->name('blockchain.retry');
        Route::patch('/rubrik-marketplace/{rubrikMarketplace}/verify', [RubrikMarketplaceController::class, 'verify'])->name('marketplace.verify');
        Route::get('/penilaian/riwayat', [AuditPenilaianController::class, 'index'])->name('penilaian.riwayat.index');
        Route::get('/penilaian/{penilaian}/riwayat', [AuditPenilaianController::class, 'show'])->name('penilaian.riwayat.show');

        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserManagementController::class, 'create'])->name('users.create');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
        Route::patch('/users/{user}/status', [UserManagementController::class, 'updateStatus'])->name('users.status');

        Route::resource('fakultas', FakultasController::class)->except('show')->parameters(['fakultas' => 'fakultas']);
        Route::resource('jurusan', JurusanController::class)->except('show');
        Route::resource('kelas', KelasController::class)->parameters(['kelas' => 'kelas']);
        Route::post('/kelas/{kelas}/students', [KelasController::class, 'addStudent'])->name('kelas.students.store');
        Route::delete('/kelas/{kelas}/students/{user}', [KelasController::class, 'removeStudent'])->name('kelas.students.destroy');
        Route::post('/kelas/{kelas}/students/{user}/move', [KelasController::class, 'moveStudent'])->name('kelas.students.move');
    });
});
