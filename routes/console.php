<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();*/

/*// Define the schedule callback
Artisan::command('schedule:run', function (Schedule $schedule) {
    // Schedule the auto-checkout command to run hourly
    $schedule->command('checkin:auto-checkout')->hourly();
});*/

app(Schedule::class)->command('checkin:auto-checkout')->hourly();

