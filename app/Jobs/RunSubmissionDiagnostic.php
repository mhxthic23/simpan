<?php

namespace App\Jobs;

use App\Contracts\AiWritingDetector;
use App\Contracts\PlagiarismChecker;
use App\Models\SelfDiagnosticLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

class RunSubmissionDiagnostic implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public readonly int $logId) {}

    public function handle(AiWritingDetector $ai, PlagiarismChecker $plagiarism): void
    {
        $log = SelfDiagnosticLog::query()->with('pengumpulanTugas')->findOrFail($this->logId);
        $submission = $log->pengumpulanTugas;
        if (! $submission || ! $submission->stored_path || ! Storage::disk('local')->exists($submission->stored_path)) {
            $log->forceFill(['status' => 'failed', 'checked_at' => now(), 'ai_result' => ['error' => 'Berkas private tidak tersedia.'], 'plagiarism_result' => ['error' => 'Berkas private tidak tersedia.']])->save();
            return;
        }
        $path = Storage::disk('local')->path($submission->stored_path);
        $filename = basename($submission->original_filename ?: $submission->stored_path);
        $contentType = $submission->mime_type ?: 'application/octet-stream';
        $consentedAt = data_get($log->ai_result, 'consented_at');
        $aiResult = $plagiarismResult = null;
        $aiScore = $similarity = null;
        try { $result = $ai->inspect($path, $filename, $contentType); $aiScore = $result['score']; $aiResult = ['provider' => config('services.simpan_diagnostics.ai_name', 'ai_detector'), 'consented_at' => $consentedAt, 'result' => $result['result'], 'checked_at' => now()->toISOString()]; }
        catch (Throwable) { $aiResult = ['provider' => config('services.simpan_diagnostics.ai_name', 'ai_detector'), 'consented_at' => $consentedAt, 'error' => 'Provider AI tidak tersedia atau pemeriksaan gagal.']; }
        try { $result = $plagiarism->inspect($path, $filename, $contentType); $similarity = $result['score']; $plagiarismResult = ['provider' => config('services.simpan_diagnostics.plagiarism_name', 'plagiarism_checker'), 'consented_at' => $consentedAt, 'result' => $result['result'], 'checked_at' => now()->toISOString()]; }
        catch (Throwable) { $plagiarismResult = ['provider' => config('services.simpan_diagnostics.plagiarism_name', 'plagiarism_checker'), 'consented_at' => $consentedAt, 'error' => 'Provider plagiarisme tidak tersedia atau pemeriksaan gagal.']; }
        $successes = (int) (! isset($aiResult['error'])) + (int) (! isset($plagiarismResult['error']));
        $log->forceFill([
            'ai_score' => $aiScore, 'similarity_score' => $similarity,
            'ai_result' => $aiResult, 'plagiarism_result' => $plagiarismResult,
            'status' => $successes === 2 ? 'completed' : ($successes === 1 ? 'partial' : 'failed'),
            'checked_at' => now(),
        ])->save();
    }
}
