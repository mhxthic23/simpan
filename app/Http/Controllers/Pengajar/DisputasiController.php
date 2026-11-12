<?php

namespace App\Http\Controllers\Pengajar;

use App\Http\Controllers\Controller;
use App\Models\Disputasi;
use App\Models\DisputasiRiwayat;
use App\Models\Penilaian;
use App\Models\Tugas;
use App\Models\User;
use App\Services\BlockchainTransactionService;
use App\Services\NotificationService;
use App\Services\PenilaianService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DisputasiController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'reviewing', 'accepted', 'rejected'])],
        ], ['status.in' => 'Filter status keberatan tidak valid.']);

        $disputasi = $this->forTeacher($request->user())
            ->with(['siswa', 'penilaian.pengumpulanTugas.tugas'])
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->latest('submitted_at')
            ->paginate(20)
            ->withQueryString();

        return view('pengajar.disputasi.index', compact('disputasi', 'filters'));
    }

    public function show(Request $request, Disputasi $disputasi): View
    {
        $disputasi = $this->forTeacher($request->user())->whereKey($disputasi->id)
            ->with(['siswa', 'penilaian.pengumpulanTugas.tugas.kelas', 'penilaian.detail.rubrik', 'riwayat.pengubah'])
            ->firstOrFail();
        $tugas = $disputasi->penilaian->pengumpulanTugas->tugas;
        $criteria = $tugas->rubrik()->orderBy('urutan')->orderBy('id')->get();
        $currentDetails = $disputasi->penilaian->detail->keyBy('rubrik_id');

        return view('pengajar.disputasi.show', compact('disputasi', 'tugas', 'criteria', 'currentDetails'));
    }

    public function review(Request $request, Disputasi $disputasi): RedirectResponse
    {
        $data = $request->validate([
            'tanggapan_pengajar' => ['nullable', 'string', 'max:10000'],
        ], [
            'tanggapan_pengajar.string' => 'Tanggapan harus berupa teks.',
            'tanggapan_pengajar.max' => 'Tanggapan maksimal 10000 karakter.',
        ]);

        DB::transaction(function () use ($request, $disputasi, $data): void {
            $disputasi = $this->forTeacher($request->user())->whereKey($disputasi->id)->lockForUpdate()->firstOrFail();
            $this->ensureOpen($disputasi);
            $oldStatus = $disputasi->status;
            $oldResponse = $disputasi->tanggapan_pengajar;
            $disputasi->status = 'reviewing';
            if (array_key_exists('tanggapan_pengajar', $data)) {
                $disputasi->tanggapan_pengajar = $data['tanggapan_pengajar'];
            }
            $disputasi->save();

            if ($oldStatus !== $disputasi->status || $oldResponse !== $disputasi->tanggapan_pengajar) {
                $this->appendHistory($disputasi, $request->user(), $oldStatus, [
                    'tanggapan_sebelumnya' => $oldResponse,
                    'tanggapan_baru' => $disputasi->tanggapan_pengajar,
                ]);
            }
        });

        $this->notifyStudent($disputasi->fresh(), 'dispute_reviewed', 'dispute:'.$disputasi->id.':reviewed');

        return back()->with('status', 'Keberatan ditandai sedang ditinjau.');
    }

    public function resolve(Request $request, Disputasi $disputasi, PenilaianService $service): RedirectResponse
    {
        $data = $request->validate([
            'keputusan_tipe' => ['required', Rule::in(['accepted', 'rejected'])],
            'keputusan' => ['required', 'string', 'min:5', 'max:10000'],
            'tanggapan_pengajar' => ['nullable', 'string', 'max:10000'],
            'scores' => ['required_if:keputusan_tipe,accepted', 'array'],
            'scores.*' => ['required_if:keputusan_tipe,accepted', 'numeric', 'decimal:0,2', 'min:0'],
            'detail_feedback' => ['sometimes', 'array'],
            'detail_feedback.*' => ['nullable', 'string', 'max:5000'],
            'feedback' => ['nullable', 'string', 'max:20000'],
        ], [
            'keputusan_tipe.required' => 'Pilih hasil keberatan.',
            'keputusan_tipe.in' => 'Hasil keberatan tidak valid.',
            'keputusan.required' => 'Alasan keputusan wajib diisi.',
            'keputusan.min' => 'Alasan keputusan minimal 5 karakter.',
            'keputusan.max' => 'Alasan keputusan maksimal 10000 karakter.',
            'scores.required_if' => 'Skor peninjauan wajib diisi saat keberatan diterima.',
            'scores.*.required_if' => 'Skor setiap kriteria wajib diisi.',
            'scores.*.numeric' => 'Skor harus berupa angka.',
            'scores.*.decimal' => 'Skor maksimal memiliki dua angka desimal.',
            'scores.*.min' => 'Skor tidak boleh kurang dari nol.',
        ]);

        DB::transaction(function () use ($request, $disputasi, $data, $service): void {
            $disputasi = $this->forTeacher($request->user())->whereKey($disputasi->id)->lockForUpdate()->firstOrFail();
            $this->ensureOpen($disputasi);
            $oldStatus = $disputasi->status;
            $oldResponse = $disputasi->tanggapan_pengajar;
            $oldDecision = $disputasi->keputusan;

            if ($data['keputusan_tipe'] === 'accepted') {
                $assessment = $disputasi->penilaian()->with('pengumpulanTugas')->firstOrFail();
                $assessment->load('detail');
                $detailFeedback = $data['detail_feedback'] ?? $assessment->detail->pluck('feedback', 'rubrik_id')->all();
                $service->save(
                    $assessment->pengumpulanTugas,
                    $request->user(),
                    $data['scores'] ?? [],
                    $detailFeedback,
                    $data['feedback'] ?? $assessment->feedback,
                    'published',
                    'Peninjauan keberatan #'.$disputasi->id.': '.$data['keputusan'],
                    'perubahan_melalui_disputasi'
                );
            }

            $disputasi->status = $data['keputusan_tipe'];
            $disputasi->tanggapan_pengajar = $data['tanggapan_pengajar'] ?? $disputasi->tanggapan_pengajar;
            $disputasi->keputusan = $data['keputusan'];
            $disputasi->resolved_by = $request->user()->id;
            $disputasi->resolved_at = now();
            $disputasi->save();

            $this->appendHistory($disputasi, $request->user(), $oldStatus, [
                'tanggapan_sebelumnya' => $oldResponse,
                'tanggapan_baru' => $disputasi->tanggapan_pengajar,
                'keputusan_sebelumnya' => $oldDecision,
                'keputusan_baru' => $disputasi->keputusan,
            ]);
        });

        $this->notifyStudent($disputasi->fresh(), 'dispute_resolved', 'dispute:'.$disputasi->id.':resolved', true);

        return redirect()->route('pengajar.disputasi.show', $disputasi)->with('status', 'Keberatan berhasil diselesaikan.');
    }

    private function forTeacher(User $user): Builder
    {
        return Disputasi::query()->whereHas('penilaian.pengumpulanTugas.tugas.kelas',
            fn (Builder $query) => $query->where('pengajar_id', $user->id));
    }

    private function ensureOpen(Disputasi $disputasi): void
    {
        if (! in_array($disputasi->status, ['pending', 'reviewing'], true)) {
            throw ValidationException::withMessages(['status' => 'Keberatan ini sudah diselesaikan dan tidak dapat diubah.']);
        }
    }

    /** @param array<string, mixed> $snapshot */
    private function appendHistory(Disputasi $disputasi, User $actor, string $oldStatus, array $snapshot): void
    {
        DisputasiRiwayat::create([
            'disputasi_id' => $disputasi->id,
            'diubah_oleh' => $actor->id,
            'status_sebelumnya' => $oldStatus,
            'status_baru' => $disputasi->status,
            'tanggapan' => $disputasi->tanggapan_pengajar,
            'keputusan' => $disputasi->keputusan,
            'snapshot_data' => $snapshot,
            'created_at' => now(),
        ]);
    }

    private function notifyStudent(Disputasi $disputasi, string $type, string $key, bool $resolved = false): void
    {
        try {
            $disputasi->load('siswa');
            app(NotificationService::class)->sendOnce(
                $disputasi->siswa,
                $resolved ? 'Keberatan diselesaikan' : 'Keberatan sedang ditinjau',
                $resolved ? 'Pengajar telah menyelesaikan keberatan Anda.' : 'Pengajar telah memperbarui status keberatan Anda.',
                $type, $key, $disputasi
            );
            app(BlockchainTransactionService::class)->record(
                $resolved ? 'dispute_resolved' : 'dispute_reviewed', $disputasi,
                ['status' => $disputasi->status, 'response_sha256' => $disputasi->tanggapan_pengajar ? hash('sha256', $disputasi->tanggapan_pengajar) : null,
                    'decision_sha256' => $disputasi->keputusan ? hash('sha256', $disputasi->keputusan) : null],
                $key
            );
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
