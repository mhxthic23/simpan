<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('simpan:blockchain-poll')->everyMinute()->withoutOverlapping();
Schedule::command('simpan:generate-reminders')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('simpan:generate-followup-reminders')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('simpan:dispatch-reminders')->everyMinute()->withoutOverlapping();
Schedule::command('simpan:close-votings')->everyMinute()->withoutOverlapping();
