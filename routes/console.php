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
