<?php

namespace App\Http\Controllers\Pengajar;

use App\Http\Controllers\Controller;
use App\Models\PengumpulanTugas;
use App\Models\Rubrik;
use App\Models\Tugas;
use App\Models\User;
use App\Services\PenilaianService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PenilaianController extends Controller
{
    public function index(Request $request): View
    {
        $pengumpulan = PengumpulanTugas::query()
            ->whereHas('tugas.kelas', fn (Builder $query) => $query->where('pengajar_id', $request->user()->id))
            ->whereDoesntHave('penilaian', fn (Builder $query) => $query->whereNotNull('published_at'))
            ->with(['siswa', 'tugas.kelas'])
            ->latest('submitted_at')
            ->paginate(20);

        return view('pengajar.penilaian.index', compact('pengumpulan'));
    }

    public function edit(Request $request, Tugas $tugas, PengumpulanTugas $submission): View
    {
        $tugas = $this->ownedTask($request->user(), $tugas);
        abort_unless($submission->tugas_id === $tugas->id, 404);
        $submission->load('siswa', 'penilaian.detail.rubrik', 'penilaian.riwayat.pengubah');
        $criteria = $tugas->rubrik()->orderBy('urutan')->orderBy('id')->get();
        $totalBobotCents = $criteria->sum(fn (Rubrik $item) => (int) round((float) $item->bobot * 100));

        return view('pengajar.penilaian.edit', compact('tugas', 'submission', 'criteria', 'totalBobotCents'));
    }

    public function save(Request $request, Tugas $tugas, PengumpulanTugas $submission, PenilaianService $service): RedirectResponse
    {
        $tugas = $this->ownedTask($request->user(), $tugas);
        abort_unless($submission->tugas_id === $tugas->id, 404);
        $assessment = $submission->penilaian;

        $data = $request->validate([
            'status' => ['required', Rule::in(['draft', 'published'])],
            'scores' => ['required', 'array', 'min:1'],
            'scores.*' => ['required', 'numeric', 'decimal:0,2', 'min:0'],
            'detail_feedback' => ['sometimes', 'array'],
            'detail_feedback.*' => ['nullable', 'string', 'max:5000'],
            'feedback' => ['nullable', 'string', 'max:20000'],
            'alasan_perubahan' => [$assessment?->published_at ? 'required' : 'nullable', 'string', 'max:5000'],
        ], [
            'status.required' => 'Status penilaian wajib dipilih.',
            'status.in' => 'Status penilaian tidak valid.',
            'scores.required' => 'Skor untuk setiap kriteria wajib diisi.',
            'scores.array' => 'Format skor tidak valid.',
            'scores.min' => 'Skor untuk setiap kriteria wajib diisi.',
            'scores.*.required' => 'Skor kriteria wajib diisi.',
            'scores.*.numeric' => 'Skor harus berupa angka.',
            'scores.*.decimal' => 'Skor maksimal memiliki dua angka desimal.',
            'scores.*.min' => 'Skor tidak boleh kurang dari nol.',
            'detail_feedback.*.string' => 'Komentar kriteria harus berupa teks.',
            'detail_feedback.*.max' => 'Komentar kriteria maksimal 5000 karakter.',
            'feedback.string' => 'Umpan balik harus berupa teks.',
            'feedback.max' => 'Umpan balik maksimal 20000 karakter.',
            'alasan_perubahan.required' => 'Alasan perubahan wajib diisi untuk nilai yang sudah dipublikasikan.',
            'alasan_perubahan.max' => 'Alasan perubahan maksimal 5000 karakter.',
        ]);

        $service->save(
            $submission,
            $request->user(),
            $data['scores'],
            $data['detail_feedback'] ?? [],
            $data['feedback'] ?? null,
            $data['status'],
            $data['alasan_perubahan'] ?? null
        );

        $message = $data['status'] === 'published'
            ? 'Penilaian berhasil disimpan dan dipublikasikan kepada siswa.'
            : 'Draft penilaian berhasil disimpan.';

        return redirect()->route('pengajar.tugas.pengumpulan.show', [$tugas, $submission])->with('status', $message);
    }

    private function ownedTask(User $user, Tugas $tugas): Tugas
    {
        return Tugas::query()->whereKey($tugas->id)
            ->whereHas('kelas', fn (Builder $query) => $query->where('pengajar_id', $user->id))
            ->firstOrFail();
    }
}
