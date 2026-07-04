<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Auth\Infrastructure\Http\Controllers\Api\AuthController;
use Modules\Auth\Infrastructure\Http\Controllers\Api\OtpAuthController;
use Modules\Auth\Infrastructure\Http\Controllers\Api\PasswordResetController;
use Modules\Auth\Infrastructure\Http\Controllers\Api\SocialAuthController;
use Modules\Auth\Infrastructure\Http\Controllers\Api\TokenController;
use Modules\Auth\Infrastructure\Http\Controllers\Api\TwoFactorStatusController;

/*
| Sanctum token API (mobile / external). Loaded with the `api` middleware group
| by AuthServiceProvider. Never call these from the Inertia web app.
*/

Route::prefix('api/auth')->group(function (): void {
    // Public: issue a token. Extra brute-force ceiling on top of account lockout.
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('api.auth.login');

    // Passwordless OTP login.
    Route::post('/otp/request', [OtpAuthController::class, 'request'])
        ->middleware('throttle:5,1')->name('api.auth.otp.request');
    Route::post('/otp/login', [OtpAuthController::class, 'login'])
        ->middleware('throttle:6,1')->name('api.auth.otp.login');

    // Passwordless password reset (6-digit code, 30 min).
    Route::post('/password/forgot', [PasswordResetController::class, 'request'])
        ->middleware('throttle:3,15')->name('api.auth.password.forgot');
    Route::post('/password/reset', [PasswordResetController::class, 'reset'])
        ->middleware('throttle:6,1')->name('api.auth.password.reset');

    // Token-based social login (mobile): exchange a provider access token.
    Route::post('/social/{provider}', SocialAuthController::class)
        ->whereIn('provider', ['google', 'github'])
        ->middleware('throttle:10,1')->name('api.auth.social');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/refresh', [AuthController::class, 'refresh'])->name('api.auth.refresh');
        Route::get('/me', [AuthController::class, 'me'])->name('api.auth.me');
        Route::post('/logout', [AuthController::class, 'logout'])->name('api.auth.logout');
        Route::get('/two-factor', TwoFactorStatusController::class)->name('api.auth.two-factor');

        // Active API "sessions" = Sanctum tokens. "others" BEFORE "{token}".
        Route::get('/sessions', [TokenController::class, 'index'])->name('api.auth.sessions.index');
        Route::delete('/sessions/others', [TokenController::class, 'destroyOthers'])->name('api.auth.sessions.others');
        Route::delete('/sessions/{token}', [TokenController::class, 'destroy'])->name('api.auth.sessions.destroy');
    });
});
