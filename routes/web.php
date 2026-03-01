<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Modules\Auth\Infrastructure\Http\Controllers\Web\OtpController;
use Modules\Auth\Infrastructure\Http\Controllers\Web\SocialiteController;
use Modules\Users\Infrastructure\Http\Controllers\Web\UserPageController;
use Modules\Users\Infrastructure\Http\Controllers\Api\UserController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', function () {
    return Inertia::render('auth/LoginPage');
})->name('login');

// ── Authenticated Routes ──────────────────────────────────────
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('dashboard/DashboardPage');
    })->name('dashboard');

    Route::get('/profile', function () {
        return Inertia::render('profile/ProfilePage');
    })->name('profile');
});
