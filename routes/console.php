<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('lottery:fetch-latest')
    ->everyFiveMinutes()
    ->between('15:45', '16:20')
    ->timezone('Asia/Bangkok')
    ->withoutOverlapping();

Schedule::command('articles:publish-scheduled')
    ->everyMinute()
    ->withoutOverlapping();

// Process MLM commissions at 00:05 on the 1st of each month (Bangkok time)
Schedule::command('commissions:process-monthly')
    ->monthlyOn(1, '00:05')
    ->timezone('Asia/Bangkok')
    ->withoutOverlapping()
    ->runInBackground();
