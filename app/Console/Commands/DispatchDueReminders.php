<?php

namespace App\Console\Commands;

use App\Contracts\NotificationChannel;
use App\Models\Reminder;
use App\Models\ReminderPreferences;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Throwable;

class DispatchDueReminders extends Command
{
    protected $signature = 'simpan:dispatch-reminders';
    protected $description = 'Mengirim pengingat SIMPAN yang sudah jatuh waktu.';

    public function handle(NotificationService $notifications, NotificationChannel $mail): int
    {
        Reminder::query()->where('status', 'pending')->where('scheduled_at', '<=', now())
            ->with('user')->orderBy('id')->chunkById(100, function ($reminders) use ($notifications, $mail): void {
                foreach ($reminders as $reminder) {
                    try {
                        $preferences = ReminderPreferences::query()->where('user_id', $reminder->user_id)->first();
                        if (! $preferences || ! $preferences->enabled) {
                            $reminder->forceFill(['status' => 'cancelled', 'error_message' => null])->save();
                            continue;
                        }
                        if ($this->inQuietHours($preferences)) {
                            $reminder->forceFill(['scheduled_at' => $this->nextAllowedTime($preferences)])->save();
                            continue;
                        }
                        if (in_array($preferences->channel, ['in_app', 'both'], true)) {
                            $notification = $notifications->sendOnce($reminder->user, $reminder->title, $reminder->message, 'reminder', 'reminder:'.$reminder->id, $reminder->tugas);
                            if (! $notification->exists) throw new \RuntimeException('Migration idempotensi notifikasi perlu diterapkan sebelum reminder in-app dikirim.');
                        }
                        if (in_array($preferences->channel, ['email', 'both'], true)) $mail->sendReminder($reminder->user, $reminder);
                        $reminder->forceFill(['status' => 'sent', 'sent_at' => now(), 'error_message' => null])->save();
                    } catch (Throwable $exception) {
                        $reminder->forceFill(['status' => 'failed', 'error_message' => 'Pengiriman gagal; periksa konfigurasi saluran notifikasi.'])->save();
                        logger()->error('Pengiriman reminder gagal.', ['reminder_id' => $reminder->id, 'exception_class' => $exception::class]);
                    }
                }
            });
        return self::SUCCESS;
    }

    private function inQuietHours(ReminderPreferences $preferences): bool
    {
        if (! $preferences->quiet_hours_start || ! $preferences->quiet_hours_end) return false;
        $now = now()->format('H:i:s');
        $start = $preferences->quiet_hours_start;
        $end = $preferences->quiet_hours_end;
        return $start <= $end ? ($now >= $start && $now < $end) : ($now >= $start || $now < $end);
    }

    private function nextAllowedTime(ReminderPreferences $preferences): Carbon
    {
        $now = now();
        $start = $preferences->quiet_hours_start;
        $end = $preferences->quiet_hours_end;
        $target = $now->copy()->setTimeFromTimeString($end);
        if ($start > $end && $now->format('H:i:s') >= $start) $target->addDay();
        return $target;
    }
}
