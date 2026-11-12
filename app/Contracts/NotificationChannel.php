<?php

namespace App\Contracts;

use App\Models\Reminder;
use App\Models\User;

interface NotificationChannel
{
    public function sendReminder(User $user, Reminder $reminder): void;
}
