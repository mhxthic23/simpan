<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Tugas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TugasController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
        ], ['q.string' => 'Kata pencarian tidak valid.', 'q.max' => 'Pencarian maksimal 255 karakter.']);

        $tugas = $this->visibleTasks($request)
            ->with(['kelas.jurusan', 'pengajar'])
            ->with(['pengumpulanTugas' => fn ($query) => $query->where('siswa_id', $request->user()->id)->orderByDesc('attempt_no')])
            ->when($filters['q'] ?? null, fn (Builder $query, string $term) => $query->where('judul', 'like', "%{$term}%"))
            ->orderBy('deadline_at')
            ->paginate(15)
            ->withQueryString();

        return view('siswa.tugas.index', compact('tugas', 'filters'));
    }

    public function show(Request $request, Tugas $tugas): View
    {
        $tugas = $this->visibleTasks($request)->whereKey($tugas->id)
            ->with(['kelas.jurusan.fakultas', 'pengajar'])
            ->firstOrFail();
        $pengumpulanSaya = $tugas->pengumpulanTugas()
            ->where('siswa_id', $request->user()->id)
            ->with(['penilaian' => fn ($query) => $query->where('status', 'published')->whereNotNull('published_at')->with(['detail.rubrik', 'disputasi'])])
            ->orderByDesc('attempt_no')
            ->get();

        return view('siswa.tugas.show', compact('tugas', 'pengumpulanSaya'));
    }

    private function visibleTasks(Request $request): Builder
    {
        return Tugas::query()
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->whereHas('kelas.siswa', fn (Builder $query) => $query->where('users.id', $request->user()->id));
    }
}
