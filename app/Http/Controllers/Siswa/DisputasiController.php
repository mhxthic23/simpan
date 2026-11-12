<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Disputasi;
use App\Models\DisputasiRiwayat;
use App\Models\Penilaian;
use App\Models\User;
use App\Services\BlockchainTransactionService;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DisputasiController extends Controller
{
    public function index(Request $request): View
    {
        $disputasi = Disputasi::query()
            ->where('siswa_id', $request->user()->id)
            ->with('penilaian.pengumpulanTugas.tugas')
            ->latest('submitted_at')
            ->paginate(20);

        return view('siswa.disputasi.index', compact('disputasi'));
    }

    public function store(Request $request, Penilaian $penilaian): RedirectResponse
    {
        $penilaian = $this->ownPublishedAssessment($request->user(), $penilaian);
        $data = $request->validate([
            'alasan' => ['required', 'string', 'min:10', 'max:10000'],
        ], [
            'alasan.required' => 'Alasan keberatan wajib diisi.',
            'alasan.string' => 'Alasan keberatan harus berupa teks.',
            'alasan.min' => 'Alasan keberatan minimal 10 karakter.',
            'alasan.max' => 'Alasan keberatan maksimal 10000 karakter.',
        ]);

        if ($penilaian->disputasi()->exists()) {
            return back()->withErrors(['alasan' => 'Keberatan untuk penilaian ini sudah pernah diajukan.']);
        }

        $dispute = DB::transaction(function () use ($request, $penilaian, $data): Disputasi {
            $dispute = Disputasi::create([
                'penilaian_id' => $penilaian->id,
                'siswa_id' => $request->user()->id,
                'alasan' => $data['alasan'],
                'status' => 'pending',
                'submitted_at' => now(),
            ]);

            DisputasiRiwayat::create([
                'disputasi_id' => $dispute->id,
                'diubah_oleh' => $request->user()->id,
                'status_sebelumnya' => null,
                'status_baru' => 'pending',
                'snapshot_data' => ['alasan' => $data['alasan']],
                'created_at' => now(),
            ]);

            return $dispute;
        });

        try {
            $penilaian->load('pengumpulanTugas.tugas.kelas.pengajar');
            $teacher = $penilaian->pengumpulanTugas->tugas->kelas->pengajar;
            if ($teacher) app(NotificationService::class)->sendOnce($teacher, 'Keberatan nilai baru', 'Siswa mengajukan keberatan atas nilai tugas.', 'dispute_submitted', 'dispute:'.$dispute->id.':submitted:teacher', $dispute);
            app(NotificationService::class)->sendOnce($request->user(), 'Keberatan berhasil diajukan', 'Pengajuan keberatan Anda tercatat dan menunggu tanggapan pengajar.', 'dispute_submitted', 'dispute:'.$dispute->id.':submitted:student', $dispute);
            app(BlockchainTransactionService::class)->record('dispute_submitted', $dispute, ['status' => 'pending', 'reason_sha256' => hash('sha256', $data['alasan'])], 'dispute:'.$dispute->id.':submitted');
        } catch (\Throwable $exception) {
            report($exception);
        }

        return redirect()->route('siswa.nilai.show', $penilaian)->with('status', 'Keberatan berhasil diajukan.');
    }

    private function ownPublishedAssessment(User $user, Penilaian $penilaian): Penilaian
    {
        return Penilaian::query()
            ->whereKey($penilaian->id)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->whereHas('pengumpulanTugas', fn (Builder $query) => $query->where('siswa_id', $user->id))
            ->firstOrFail();
    }
}
