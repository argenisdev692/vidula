<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Spatie\OneTimePasswords\Models\OneTimePassword;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Prune expired / consumed one-time passwords daily (spatie/laravel-one-time-passwords).
Schedule::command('model:prune', ['--model' => [OneTimePassword::class]])->daily();

// Tiered retention: archive activity-log rows past the 90-day hot window to R2,
// then purge them from the database. Runs off-peak so large trims don't compete
// with request traffic.
Schedule::command('activity-log:archive')->dailyAt('02:30')->onOneServer();

// Database/app backups (spatie/laravel-backup). Clean first (prune old archives),
// then create the fresh backup, then verify destination health.
Schedule::command('backup:clean')->daily()->at('01:00')->onOneServer();
Schedule::command('backup:run')->daily()->at('02:00')->onOneServer();
Schedule::command('backup:monitor')->daily()->at('03:00')->onOneServer();
