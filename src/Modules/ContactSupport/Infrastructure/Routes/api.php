<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\ContactSupport\Infrastructure\Http\Controllers\Api\ContactSupportApiController;
use Modules\ContactSupport\Infrastructure\Http\Controllers\Api\PublicContactApiController;

/*
| PUBLIC contact-support API for the marketing landing page. Unauthenticated,
| stateless (no session/CSRF), tightly throttled per IP. Defined BEFORE the
| authenticated group so GET /honeypot is never shadowed by GET /{uuid}.
| Documented by Scramble under /api/contact-supports.
*/
Route::prefix('contact-supports')
    ->name('api.contact-supports.')
    ->group(function (): void {
        Route::post('/', [PublicContactApiController::class, 'store'])
            ->middleware('throttle:5,1')
            ->name('submit');

        Route::get('/honeypot', [PublicContactApiController::class, 'honeypot'])
            ->middleware('throttle:30,1')
            ->name('honeypot');
    });

/*
| Contact-support API routes. Secondary surface for Sanctum-authenticated
| clients. Documented by Scramble under /api/contact-supports.
*/
Route::middleware(['auth:sanctum', 'throttle:60,1'])
    ->prefix('contact-supports')
    ->name('api.contact-supports.')
    ->group(function (): void {
        Route::get('/', [ContactSupportApiController::class, 'index'])->name('index');
        Route::get('/{uuid}', [ContactSupportApiController::class, 'show'])
            ->whereUuid('uuid')
            ->name('show');
    });
