<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Send guidance appointment reminders periodically. Requires a running
// scheduler (`php artisan schedule:work` locally or a cron entry in production).
Schedule::command('guidance:send-reminders')->hourly()->withoutOverlapping();
