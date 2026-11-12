<?php

namespace App\Services\Integrations;

use App\Contracts\NotificationChannel;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class MailReminderChannel implements NotificationChannel
{
    public function sendReminder(User $user, Reminder $reminder): void
    {
        Mail::raw($reminder->message, function ($message) use ($user, $reminder): void {
            $message->to($user->email)->subject($reminder->title);
        });
    }
}
