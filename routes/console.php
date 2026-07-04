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
