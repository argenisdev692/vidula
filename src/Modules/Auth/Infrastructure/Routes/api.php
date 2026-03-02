<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Auth\Infrastructure\Http\Controllers\Api\OtpController;
use Modules\Auth\Infrastructure\Http\Controllers\Api\SocialiteController;
use Illuminate\Http\Request;

// ── API Auth Routes ───────────────────────────────────────────
Route::middleware(['api'])->prefix('api/auth')->group(function () {

    Route::post('/otp/send', [OtpController::class, 'send'])
        ->name('api.auth.otp.send');

    Route::post('/otp/verify', [OtpController::class, 'verify'])
        ->name('api.auth.otp.verify');

    // Mobile apps usually send the token directly using this endpoint:
    Route::post('/{provider}/callback', [SocialiteController::class, 'callback'])
        ->name('api.auth.socialite.callback');

    // Protected routes
    Route::middleware('auth:sanctum')->get('/me', function (Request $request) {
        return response()->json($request->user());
    })->name('api.auth.me');

    Route::middleware('auth:sanctum')->post('/logout', [OtpController::class, 'logout'])
        ->name('api.auth.logout');
});
