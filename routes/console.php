<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('inquiries:fetch-replies')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command('deals:reconcile-payments')
    ->everyTenMinutes()
    ->withoutOverlapping();

Schedule::command('queue:work --once --stop-when-empty')
    ->everyMinute()
    ->withoutOverlapping();
