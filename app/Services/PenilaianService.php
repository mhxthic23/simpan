<?php

namespace App\Services;

use App\Models\PengumpulanTugas;
use App\Models\Penilaian;
use App\Models\PenilaianDetail;
use App\Models\PenilaianRiwayat;
use App\Models\Rubrik;
use App\Models\User;
use App\Services\BlockchainTransactionService;
use App\Services\NotificationService;
use App\Services\ReputationLedgerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PenilaianService
{
    /**
     * @param array<string|int, int|float|string> $scores
     * @param array<string|int, string|null> $detailFeedback
     */
    public function save(
        PengumpulanTugas $submission,
        User $actor,
        array $scores,
        array $detailFeedback,
        ?string $feedback,
        string $requestedStatus,
        ?string $reason = null,
        string $event = 'penilaian_diperbarui'
    ): Penilaian {
        return DB::transaction(function () use ($submission, $actor, $scores, $detailFeedback, $feedback, $requestedStatus, $reason, $event): Penilaian {
            $lockedSubmission = PengumpulanTugas::query()->whereKey($submission->id)->lockForUpdate()->firstOrFail();
            $criteria = Rubrik::query()->where('tugas_id', $lockedSubmission->tugas_id)->orderBy('urutan')->orderBy('id')->get();

            if ($criteria->isEmpty() || $criteria->sum(fn (Rubrik $item) => (int) round((float) $item->bobot * 100)) !== 10000) {
                throw ValidationException::withMessages(['rubrik' => 'Rubrik harus memiliki total bobot tepat 100% sebelum penilaian dapat disimpan.']);
            }

            $expectedIds = $criteria->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
            $providedIds = collect(array_keys($scores))->map(fn ($id) => (int) $id)->sort()->values()->all();
            if ($expectedIds !== $providedIds) {
                throw ValidationException::withMessages(['scores' => 'Skor harus diberikan tepat satu kali untuk setiap kriteria rubrik tugas ini.']);
            }

            $contributions = [];
            foreach ($criteria as $criterion) {
                $score = $scores[$criterion->id] ?? $scores[(string) $criterion->id] ?? null;
                if (! is_numeric($score) || (float) $score < 0 || (float) $score > (float) $criterion->skor_maksimum) {
                    throw ValidationException::withMessages(["scores.{$criterion->id}" => "Skor {$criterion->nama} harus berada antara 0 dan {$criterion->skor_maksimum}."]);
                }

                $contributions[] = ((float) $score / (float) $criterion->skor_maksimum) * (float) $criterion->bobot;
            }
            $total = round(array_sum($contributions), 2);

            $assessment = Penilaian::query()
                ->where('pengumpulan_tugas_id', $lockedSubmission->id)
                ->lockForUpdate()
                ->first();
            $before = $assessment ? $this->snapshot($assessment) : null;

            if ($assessment?->published_at !== null && trim((string) $reason) === '') {
                throw ValidationException::withMessages(['alasan_perubahan' => 'Alasan perubahan wajib diisi untuk nilai yang sudah dipublikasikan.']);
            }

            $status = $assessment?->published_at !== null
                ? 'published'
                : $requestedStatus;
            $publishedAt = $status === 'published'
                ? ($assessment?->published_at ?? now())
                : null;

            if ($assessment === null) {
                $assessment = new Penilaian();
                $assessment->pengumpulan_tugas_id = $lockedSubmission->id;
                $assessment->pengajar_id = $actor->id;
            }
            $assessment->pengajar_id = $actor->id;
            $assessment->status = $status;
            $assessment->nilai_total = $total;
            $assessment->feedback = $feedback;
            $assessment->graded_at = now();
            $assessment->published_at = $publishedAt;
            $assessment->save();

            foreach ($criteria as $criterion) {
                $score = $scores[$criterion->id] ?? $scores[(string) $criterion->id];
                PenilaianDetail::query()->updateOrCreate(
                    ['penilaian_id' => $assessment->id, 'rubrik_id' => $criterion->id],
                    [
                        'skor' => $score,
                        'feedback' => $detailFeedback[$criterion->id] ?? $detailFeedback[(string) $criterion->id] ?? null,
                    ]
                );
            }

            $assessment->detail()->whereNotIn('rubrik_id', $criteria->pluck('id'))->delete();
            $after = $this->snapshot($assessment->fresh());

            $history = null;
            if ($before !== $after) {
                $history = PenilaianRiwayat::create([
                    'penilaian_id' => $assessment->id,
                    'diubah_oleh' => $actor->id,
                    'nilai_sebelumnya' => $before['nilai_total'] ?? null,
                    'nilai_baru' => $total,
                    'alasan_perubahan' => $reason,
                    'snapshot_data' => ['event' => $event, 'sebelum' => $before, 'sesudah' => $after],
                ]);
            }

            if ($status === 'published' && $history !== null) {
                $assessmentId = $assessment->id;
                $historyId = $history->id;
                $studentId = $lockedSubmission->siswa_id;
                $assessmentTotal = $total;
                $chainEvent = $event === 'perubahan_melalui_disputasi' ? 'dispute_grade_updated' : (($before['status'] ?? null) === 'published' ? 'grade_updated' : 'grade_published');
                DB::afterCommit(function () use ($assessmentId, $historyId, $studentId, $assessmentTotal, $event, $chainEvent): void {
                    try {
                        $assessment = Penilaian::query()->findOrFail($assessmentId);
                        $student = User::query()->findOrFail($studentId);
                        app(NotificationService::class)->sendOnce(
                            $student, 'Nilai dipublikasikan', 'Nilai tugas Anda telah dipublikasikan.', 'grade_published',
                            'assessment:'.$assessmentId.':history:'.$historyId, $assessment
                        );
                        app(BlockchainTransactionService::class)->record(
                            $chainEvent,
                            $assessment,
                            ['nilai_total' => $assessmentTotal, 'history_id' => $historyId, 'event' => $event],
                            'assessment:'.$assessmentId.':history:'.$historyId
                        );
                        $tokens = (int) config('services.simpan_reputation.grade_published_token', 0);
                        if ($tokens !== 0) app(ReputationLedgerService::class)->record($student, 'grade_published', $tokens,
                            'assessment:'.$assessmentId.':history:'.$historyId, 'Publikasi nilai', $assessment);
                    } catch (\Throwable $exception) {
                        report($exception);
                    }
                });
            }

            return $assessment->fresh(['detail.rubrik', 'pengumpulanTugas']);
        });
    }

    /** @return array<string, mixed> */
    private function snapshot(Penilaian $assessment): array
    {
        $assessment->load('detail.rubrik');
        $details = $assessment->detail
            ->sortBy(fn (PenilaianDetail $detail) => $detail->rubrik?->urutan ?? 0)
            ->values()
            ->map(fn (PenilaianDetail $detail) => [
                'rubrik_id' => $detail->rubrik_id,
                'nama' => $detail->rubrik?->nama,
                'bobot' => $detail->rubrik?->bobot,
                'skor_maksimum' => $detail->rubrik?->skor_maksimum,
                'skor' => $detail->skor,
                'feedback' => $detail->feedback,
            ])->all();

        return [
            'status' => $assessment->status,
            'nilai_total' => $assessment->nilai_total,
            'feedback' => $assessment->feedback,
            'published_at' => $assessment->published_at?->toISOString(),
            'detail' => $details,
        ];
    }
}
