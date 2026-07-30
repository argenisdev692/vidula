<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Appointment\Infrastructure\Http\Controllers\Api\AppointmentApiController;
use Modules\Appointment\Infrastructure\Http\Controllers\Api\PublicAppointmentApiController;

/*
| PUBLIC appointment-booking API for the Astro marketing landing page.
| Stateless (no session/CSRF), gated by `crm.token` (CRM_API_TOKEN), tightly
| throttled per IP. Defined BEFORE the authenticated group so GET /honeypot
| is never shadowed by GET /{uuid}. Documented by Scramble under /api/appointments.
*/
Route::prefix('appointments')
    ->name('api.appointments.')
    ->middleware('crm.token')
    ->group(function (): void {
        Route::post('/', [PublicAppointmentApiController::class, 'store'])
            ->middleware('throttle:5,1')
            ->name('book');

        Route::get('/honeypot', [PublicAppointmentApiController::class, 'honeypot'])
            ->middleware('throttle:30,1')
            ->name('honeypot');
    });

/*
| Appointment API routes. Secondary surface for Sanctum-authenticated clients
| (read-only — admin writes stay on the web/Inertia surface).
*/
Route::middleware(['auth:sanctum', 'throttle:60,1'])
    ->prefix('appointments')
    ->name('api.appointments.')
    ->group(function (): void {
        Route::get('/', [AppointmentApiController::class, 'index'])
            ->middleware('permission:VIEW_ANY_APPOINTMENTS')->name('index');
        Route::get('/{uuid}', [AppointmentApiController::class, 'show'])
            ->middleware('permission:VIEW_APPOINTMENTS')
            ->whereUuid('uuid')
            ->name('show');
    });
