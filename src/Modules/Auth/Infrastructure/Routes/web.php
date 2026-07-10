<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Auth\Infrastructure\Http\Controllers\AvailabilityController;
use Modules\Auth\Infrastructure\Http\Controllers\Web\AuthPageController;
use Modules\Auth\Infrastructure\Http\Controllers\Web\OtpAuthController;
use Modules\Auth\Infrastructure\Http\Controllers\Web\PasswordResetController;
use Modules\Auth\Infrastructure\Http\Controllers\Web\ProfileController;
use Modules\Auth\Infrastructure\Http\Controllers\Web\ProfilePhotoController;
use Modules\Auth\Infrastructure\Http\Controllers\Web\SessionController;
use Modules\Auth\Infrastructure\Http\Controllers\Web\SocialAuthController;
use Modules\Auth\Infrastructure\Http\Controllers\Web\TrustedDeviceController;

/*
| Web (session + Inertia) auth routes.
|
| The base register / login / logout / email-verification / password-reset
| GET pages are rendered by Fortify via the *View callbacks bound to
| AuthPageController (see AuthServiceProvider); the POST/PUT/DELETE actions for
| those flows are owned by Fortify. This file adds passwordless OTP login,
| OAuth social login, and 2FA management.
*/

// ---- Guest flows: OTP login + OAuth ----------------------------------------
Route::middleware('web')->group(function (): void {
    // Realtime username/email availability for the register form (guest). Ignores
    // no row; rate-limited to blunt account enumeration.
    Route::get('/register/availability', AvailabilityController::class)
        ->middleware('throttle:30,1')->name('register.availability');

    // Passwordless OTP login.
    Route::get('/auth/otp', [AuthPageController::class, 'otpLogin'])->name('auth.otp');
    Route::post('/auth/otp/request', [OtpAuthController::class, 'request'])
        ->middleware('throttle:5,1')->name('auth.otp.request');
    Route::post('/auth/otp/login', [OtpAuthController::class, 'login'])
        ->middleware('throttle:6,1')->name('auth.otp.login');

    // Passwordless password reset (6-digit code, 30 min) — replaces Fortify's
    // link+token reset. Request is capped at 3 / 15 min per IP (prompt §2).
    Route::get('/forgot-password', [AuthPageController::class, 'requestPasswordReset'])
        ->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'request'])
        ->middleware('throttle:3,15')->name('password.email');
    Route::get('/reset-password', [AuthPageController::class, 'resetPassword'])
        ->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])
        ->middleware('throttle:6,1')->name('password.update');

    // OAuth redirect + callback (Google / GitHub).
    Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])
        ->whereIn('provider', ['google', 'github'])->name('auth.social.redirect');
    Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
        ->whereIn('provider', ['google', 'github'])->name('auth.social.callback');
});

// ---- Authenticated security settings ---------------------------------------
Route::middleware(['web', 'auth'])->group(function (): void {
    // Unlink a connected social account.
    Route::delete('/auth/social/{uuid}', [SocialAuthController::class, 'unlink'])
        ->whereUuid('uuid')->name('auth.social.unlink');

    // 2FA setup / security settings page (NOT behind two-factor.enforce to
    // avoid a redirect loop for admins being forced to set up 2FA).
    Route::get('/two-factor/setup', [AuthPageController::class, 'twoFactorSetup'])
        ->name('two-factor.setup');

    // Trust the current device for 30 days (called after a successful challenge).
    Route::post('/two-factor/trust-device', [TrustedDeviceController::class, 'trust'])
        ->name('two-factor.trust-device');

    // Revoke a specific trusted device.
    Route::delete('/two-factor/trusted-devices/{uuid}', [TrustedDeviceController::class, 'revoke'])
        ->whereUuid('uuid')->name('two-factor.trusted-devices.revoke');

    // Forced password-update screen when the current password has expired.
    Route::get('/password/expired', [AuthPageController::class, 'passwordExpired'])->name('password.expired');

    // Self-service profile page. Rendering only — the profile fields are saved by
    // Fortify's PUT /user/profile-information (UpdateUserProfileInformation) and
    // the password by PUT /user/password.
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');

    // Realtime username/email availability check for the profile form (excludes
    // the current user's own record — see AvailabilityController).
    Route::get('/profile/availability', AvailabilityController::class)
        ->middleware('throttle:60,1')->name('profile.availability');

    // Self-service profile photo (stored on Cloudflare R2, private + signed URL).
    Route::post('/profile/photo', [ProfilePhotoController::class, 'update'])
        ->middleware('throttle:10,1')->name('profile.photo.update');
    Route::delete('/profile/photo', [ProfilePhotoController::class, 'destroy'])
        ->name('profile.photo.destroy');

    // Client-driven idle logout that stores the intended return path and flags the
    // login screen's "session expired" banner. ("Stay signed in" simply issues a
    // partial Inertia reload, which refreshes the rolling session lifetime.)
    Route::post('/session/idle-logout', [SessionController::class, 'idleLogout'])->name('session.idle-logout');

    // Active browser sessions (prompt §6). Static "others"/"all" BEFORE "{session}".
    Route::get('/sessions', [SessionController::class, 'index'])->name('sessions.index');
    Route::delete('/sessions/others', [SessionController::class, 'destroyOthers'])->name('sessions.others');
    Route::delete('/sessions/all', [SessionController::class, 'destroyAll'])->name('sessions.all');
    Route::delete('/sessions/{session}', [SessionController::class, 'destroy'])->name('sessions.destroy');
});
