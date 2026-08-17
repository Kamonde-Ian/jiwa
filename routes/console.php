<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('investments:credit-interest')->dailyAt('00:10');
Schedule::command('trading:run-bot')->dailyAt('00:20');
Schedule::command('admin:distribute-earnings')->dailyAt('00:30');
