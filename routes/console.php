<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Release lapsed booking holds so their slots become available again.
Schedule::command('bookings:expire-holds')->everyMinute();

// Release lapsed open-play registration holds so their slots free up.
Schedule::command('open-play:expire-holds')->everyMinute();

// Remind customers of confirmed bookings happening within the next day.
Schedule::command('bookings:send-reminders')->hourly();

// Alert customers ~10 minutes before their confirmed booking starts.
Schedule::command('bookings:send-starting-soon')->everyMinute();

// Remind registrants of open-play sessions happening within the next day.
Schedule::command('open-play:send-reminders')->hourly();
