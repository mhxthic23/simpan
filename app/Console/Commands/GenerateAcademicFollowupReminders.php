<?php

namespace App\Console\Commands;

use App\Models\Disputasi;
use App\Models\PengumpulanTugas;
use App\Models\Reminder;
use App\Models\ReminderPreferences;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GenerateAcademicFollowupReminders extends Command
{
    protected $signature = 'simpan:generate-followup-reminders';
    protected $description = 'Membuat pengingat untuk penilaian dan disputasi yang melewati SLA yang dikonfigurasi.';

    public function handle(): int
    {
        $hours = (int) config('services.simpan_reminders.followup_hours', 0);
        if ($hours < 1) {
            $this->comment('Pengingat tindak lanjut nonaktif; atur SIMPAN_REMINDER_FOLLOWUP_HOURS untuk mengaktifkan SLA.');
            return self::SUCCESS;
        }

        $created = 0;
        PengumpulanTugas::query()->whereNotNull('submitted_at')->whereDoesntHave('penilaian')
            ->with(['tugas.kelas'])
            ->chunkById(100, function ($submissions) use ($hours, &$created): void {
                foreach ($submissions as $submission) {
                    $teacherId = $submission->tugas?->kelas?->pengajar_id;
                    $due = Carbon::parse($submission->submitted_at)->addHours($hours);
                    if (! $teacherId || $due->isFuture()) continue;
                    $created += $this->createIfEnabled($teacherId, $submission->tugas_id, 'Penilaian tertunda #'.$submission->id,
                        'Pengumpulan tugas #'.$submission->id.' telah menunggu penilaian melewati SLA '.$hours.' jam.', $due);
                }
            });

        Disputasi::query()->whereIn('status', ['pending', 'reviewing'])->with('penilaian')
            ->chunkById(100, function ($disputes) use ($hours, &$created): void {
                foreach ($disputes as $dispute) {
                    $teacherId = $dispute->penilaian?->pengajar_id;
                    $due = Carbon::parse($dispute->submitted_at)->addHours($hours);
                    if (! $teacherId || $due->isFuture()) continue;
                    $taskId = $dispute->penilaian?->pengumpulanTugas?->tugas_id;
                    $created += $this->createIfEnabled($teacherId, $taskId, 'Disputasi menunggu tanggapan #'.$dispute->id,
                        'Disputasi #'.$dispute->id.' masih menunggu tanggapan melewati SLA '.$hours.' jam.', $due);
                }
            });

        $this->info("{$created} pengingat tindak lanjut baru dibuat.");
        return self::SUCCESS;
    }

    private function createIfEnabled(int $userId, ?int $taskId, string $title, string $message, Carbon $due): int
    {
        $preferences = ReminderPreferences::query()->where('user_id', $userId)->first();
        if (! $preferences || ! $preferences->enabled) return 0;

        $existing = Reminder::query()->where('user_id', $userId)->where('title', $title)->exists();
        if ($existing) return 0;

        Reminder::query()->create([
            'user_id' => $userId,
            'tugas_id' => $taskId,
            'title' => $title,
            'message' => $message,
            'scheduled_at' => $due,
            'status' => 'pending',
        ]);

        return 1;
    }
}
