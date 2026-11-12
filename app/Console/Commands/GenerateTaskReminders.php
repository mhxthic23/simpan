<?php

namespace App\Console\Commands;

use App\Models\KelasSiswa;
use App\Models\Reminder;
use App\Models\ReminderPreferences;
use App\Models\Tugas;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GenerateTaskReminders extends Command
{
    protected $signature = 'simpan:generate-reminders';
    protected $description = 'Membuat pengingat tenggat untuk siswa yang belum mengumpulkan tugas.';

    public function handle(): int
    {
        $created = 0;
        Tugas::query()->where('status', 'published')->whereNotNull('published_at')->where('deadline_at', '>', now())
            ->with('kelas.siswa')->chunkById(100, function ($tasks) use (&$created): void {
                foreach ($tasks as $task) {
                    foreach ($task->kelas->siswa->where('role', 'siswa') as $student) {
                        if ($task->pengumpulanTugas()->where('siswa_id', $student->id)->exists()) continue;
                        $preferences = ReminderPreferences::query()->where('user_id', $student->id)->first();
                        if (! $preferences || ! $preferences->enabled) continue;
                        foreach ($preferences->reminder_before_minutes ?: [1440, 60] as $minutes) {
                            $scheduledAt = Carbon::parse($task->deadline_at)->subMinutes((int) $minutes);
                            if ($scheduledAt->lessThanOrEqualTo(now())) continue;
                            $reminder = Reminder::query()->firstOrCreate([
                                'user_id' => $student->id, 'tugas_id' => $task->id, 'scheduled_at' => $scheduledAt,
                            ], [
                                'title' => 'Pengingat tenggat tugas',
                                'message' => 'Tugas "'.$task->judul.'" memiliki tenggat '.$task->deadline_at->format('d M Y H:i').'.',
                                'status' => 'pending',
                            ]);
                            if ($reminder->wasRecentlyCreated) $created++;
                        }
                    }
                }
            });
        $this->info("{$created} pengingat baru dibuat.");
        return self::SUCCESS;
    }
}
