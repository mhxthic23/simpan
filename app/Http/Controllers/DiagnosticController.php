<?php

namespace App\Http\Controllers;

use App\Jobs\RunSubmissionDiagnostic;
use App\Models\PengumpulanTugas;
use App\Models\SelfDiagnosticLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DiagnosticController extends Controller
{
    public function studentIndex(Request $request): View
    {
        $submissions = PengumpulanTugas::query()->where('siswa_id', $request->user()->id)
            ->with(['tugas', 'selfDiagnosticLogs' => fn ($q) => $q->latest()->limit(3)])
            ->latest('submitted_at')->paginate(20);
        return view('diagnostic.student', compact('submissions'));
    }

    public function run(Request $request, PengumpulanTugas $submission): RedirectResponse
    {
        $submission = PengumpulanTugas::query()->whereKey($submission->id)->where('siswa_id', $request->user()->id)->firstOrFail();
        $request->validate(['consent_external' => ['required', 'accepted']], ['consent_external.accepted' => 'Persetujuan pengiriman dokumen ke provider eksternal wajib diberikan untuk pemeriksaan ini.']);
        abort_unless($submission->status === 'submitted' && $submission->stored_path && Storage::disk('local')->exists($submission->stored_path), 404);
        $log = SelfDiagnosticLog::create([
            'pengumpulan_tugas_id' => $submission->id, 'siswa_id' => $request->user()->id,
            'status' => 'queued_with_consent', 'checked_at' => now(),
            'ai_result' => ['consent_external' => true, 'consented_at' => now()->toISOString()],
            'plagiarism_result' => ['consent_external' => true, 'consented_at' => now()->toISOString()],
        ]);
        RunSubmissionDiagnostic::dispatch($log->id);
        return back()->with('status', 'Pemeriksaan dijadwalkan. Hasil bersifat indikatif dan bukan bukti pelanggaran.');
    }

    public function teacherIndex(Request $request): View
    {
        $logs = SelfDiagnosticLog::query()->whereHas('pengumpulanTugas.tugas.kelas', fn (Builder $q) => $q->where('pengajar_id', $request->user()->id))
            ->with(['siswa', 'pengumpulanTugas.tugas'])->latest('checked_at')->paginate(25);
        return view('diagnostic.teacher', compact('logs'));
    }

    public function teacherShow(Request $request, SelfDiagnosticLog $log): View
    {
        $log = SelfDiagnosticLog::query()->whereKey($log->id)->whereHas('pengumpulanTugas.tugas.kelas', fn (Builder $q) => $q->where('pengajar_id', $request->user()->id))
            ->with(['siswa', 'pengumpulanTugas.tugas'])->firstOrFail();
        return view('diagnostic.show', compact('log'));
    }
}
