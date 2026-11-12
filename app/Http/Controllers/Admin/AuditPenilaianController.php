<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Penilaian;
use App\Models\PenilaianRiwayat;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditPenilaianController extends Controller
{
    public function index(): View
    {
        $riwayat = PenilaianRiwayat::query()
            ->with(['penilaian.pengumpulanTugas.siswa', 'pengubah'])
            ->latest('created_at')
            ->paginate(30);

        return view('admin.penilaian.riwayat-index', compact('riwayat'));
    }

    public function show(Penilaian $penilaian): View
    {
        $penilaian->load(['pengumpulanTugas.siswa', 'pengumpulanTugas.tugas', 'riwayat.pengubah', 'detail.rubrik']);

        return view('admin.penilaian.riwayat-show', compact('penilaian'));
    }
}
