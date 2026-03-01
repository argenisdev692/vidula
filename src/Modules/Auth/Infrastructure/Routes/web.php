<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Auth\Infrastructure\Http\Controllers\Web\OtpController;
use Modules\Auth\Infrastructure\Http\Controllers\Web\SocialiteController;

// ── Socialite OAuth Routes ────────────────────────────────────
Route::middleware(['web'])->group(function () {
    Route::get('/auth/{provider}', [SocialiteController::class, 'redirect'])
        ->name('socialite.redirect');

    Route::get('/auth/{provider}/callback', [SocialiteController::class, 'callback'])
        ->name('socialite.callback');
});

// ── OTP Auth Routes (guest + rate limited) ────────────────────
Route::middleware(['guest', 'auth.rate-limit'])->group(function () {
    Route::post('/login/otp/send', [OtpController::class, 'send'])
        ->name('login.otp.send');

    Route::post('/login/otp/verify', [OtpController::class, 'verify'])
        ->name('login.otp.verify');
});
