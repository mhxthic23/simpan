<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Penilaian;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NilaiController extends Controller
{
    public function index(Request $request): View
    {
        $penilaian = $this->publishedFor($request->user())
            ->with(['pengumpulanTugas.tugas', 'detail.rubrik', 'disputasi'])
            ->latest('published_at')
            ->paginate(20);

        return view('siswa.nilai.index', compact('penilaian'));
    }

    public function show(Request $request, Penilaian $penilaian): View
    {
        $penilaian = $this->publishedFor($request->user())
            ->whereKey($penilaian->id)
            ->with(['pengumpulanTugas.tugas', 'detail.rubrik', 'riwayat', 'disputasi.riwayat'])
            ->firstOrFail();
        $riwayatSiswa = $penilaian->riwayat
            ->filter(fn ($entry) => data_get($entry->snapshot_data, 'sesudah.status') === 'published')
            ->map(function ($entry) {
                $sebelumDipublikasikan = data_get($entry->snapshot_data, 'sebelum.status') !== 'published';

                return [
                    'nilai_sebelumnya' => $sebelumDipublikasikan ? null : $entry->nilai_sebelumnya,
                    'nilai_baru' => $entry->nilai_baru,
                    'created_at' => $entry->created_at,
                ];
            });

        return view('siswa.nilai.show', compact('penilaian', 'riwayatSiswa'));
    }

    private function publishedFor(User $user): Builder
    {
        return Penilaian::query()
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->whereHas('pengumpulanTugas', fn (Builder $query) => $query->where('siswa_id', $user->id));
    }
}
