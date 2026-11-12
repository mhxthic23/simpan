<?php

namespace App\Http\Controllers\Pengajar;

use App\Http\Controllers\Controller;
use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortfolioSiswaController extends Controller
{
    public function index(Request $request): View
    {
        $students = User::query()->where('role', 'siswa')
            ->whereHas('keanggotaanKelas.kelas', fn (Builder $query) => $query->where('pengajar_id', $request->user()->id))
            ->whereHas('portfolios', fn (Builder $query) => $query->where('is_public', true)->whereNotNull('published_at'))
            ->withCount(['portfolios as public_portfolios_count' => fn (Builder $query) => $query->where('is_public', true)->whereNotNull('published_at')])
            ->orderBy('name')->paginate(20);

        return view('portfolio.teacher-index', compact('students'));
    }

    public function show(Request $request, User $siswa): View
    {
        abort_unless($siswa->role === 'siswa' && $siswa->keanggotaanKelas()->whereHas('kelas', fn (Builder $q) => $q->where('pengajar_id', $request->user()->id))->exists(), 404);
        $portfolio = Portfolio::query()->where('siswa_id', $siswa->id)->where('is_public', true)->whereNotNull('published_at')
            ->with(['pengumpulanTugas.tugas', 'penilaian'])->latest()->paginate(20);
        return view('portfolio.teacher', compact('portfolio', 'siswa'));
    }
}
