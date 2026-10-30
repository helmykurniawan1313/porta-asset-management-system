<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Brand Health: keep Instagram tokens alive, then daily captures for connected accounts and linked posts.
Schedule::command('brand-health:refresh-instagram-tokens')->dailyAt('16:30')->withoutOverlapping();
Schedule::command('brand-health:capture-daily-snapshots')->dailyAt('17:00')->withoutOverlapping();
Schedule::command('brand-health:capture-performance-snapshots')->dailyAt('17:15')->withoutOverlapping();
