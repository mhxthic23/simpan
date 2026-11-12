<?php

namespace App\Http\Controllers\Pengajar;

use App\Http\Controllers\Controller;
use App\Models\PengumpulanTugas;
use App\Models\Tugas;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PengumpulanController extends Controller
{
    public function index(Request $request, Tugas $tugas): View
    {
        $tugas = $this->ownedTask($request->user(), $tugas);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:submitted'],
        ], [
            'q.string' => 'Kata pencarian tidak valid.',
            'q.max' => 'Pencarian maksimal 255 karakter.',
            'status.in' => 'Filter status tidak valid.',
        ]);

        $pengumpulan = $tugas->pengumpulanTugas()
            ->with('siswa')
            ->when($filters['q'] ?? null, fn (Builder $query, string $term) => $query->whereHas('siswa', fn (Builder $siswa) => $siswa->where('name', 'like', "%{$term}%")))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderByDesc('submitted_at')
            ->paginate(20)
            ->withQueryString();

        return view('pengajar.pengumpulan.index', compact('tugas', 'pengumpulan', 'filters'));
    }

    public function missing(Request $request, Tugas $tugas): View
    {
        $tugas = $this->ownedTask($request->user(), $tugas);
        $siswaBelumMengumpulkan = User::query()
            ->where('role', 'siswa')
            ->whereHas('keanggotaanKelas', fn (Builder $query) => $query->where('kelas_id', $tugas->kelas_id))
            ->whereDoesntHave('pengumpulanTugas', fn (Builder $query) => $query->where('tugas_id', $tugas->id))
            ->orderBy('name')
            ->paginate(20);

        return view('pengajar.pengumpulan.missing', compact('tugas', 'siswaBelumMengumpulkan'));
    }

    public function show(Request $request, Tugas $tugas, PengumpulanTugas $submission): View
    {
        $tugas = $this->ownedTask($request->user(), $tugas);
        abort_unless($submission->tugas_id === $tugas->id, 404);
        $submission->load('siswa', 'tugas.kelas');
        $pengumpulanTugas = $submission;

        return view('pengajar.pengumpulan.show', compact('tugas', 'pengumpulanTugas'));
    }

    public function download(Request $request, PengumpulanTugas $submission): StreamedResponse
    {
        $this->ownedTask($request->user(), $submission->tugas);
        abort_unless($submission->stored_path && Storage::disk('local')->exists($submission->stored_path), 404);

        return Storage::disk('local')->download(
            $submission->stored_path,
            $this->downloadName($submission->original_filename)
        );
    }

    private function ownedTask(User $user, Tugas $tugas): Tugas
    {
        return Tugas::query()
            ->whereKey($tugas->id)
            ->whereHas('kelas', fn (Builder $query) => $query->where('pengajar_id', $user->id))
            ->firstOrFail();
    }

    private function downloadName(?string $name): string
    {
        $name = str_replace('\\', '/', $name ?? 'pengumpulan');
        $name = basename($name);
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?: 'pengumpulan';

        return mb_substr($name, 0, 255);
    }
}
