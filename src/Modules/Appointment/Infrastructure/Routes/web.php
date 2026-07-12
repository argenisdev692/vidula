<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Appointment\Infrastructure\Http\Controllers\AppointmentController;
use Modules\Appointment\Infrastructure\Http\Controllers\AppointmentExportController;

/*
| Appointment pipeline management — web (session + Inertia).
|
| Management routes are gated by `permission:*_APPOINTMENTS` (UI authz uses
| permissions, never roles). Static segments (`/export`, `/bulk-*`, business
| actions) are declared BEFORE the `{uuid}` wildcard so they are never
| captured as a UUID. Public booking lives on the API surface only — the
| Astro landing page never touches session-authenticated routes.
*/
Route::middleware(['web', 'auth', 'throttle:60,1'])->prefix('appointments')->name('appointments.')->group(function (): void {
    Route::get('/', [AppointmentController::class, 'index'])
        ->middleware('permission:VIEW_ANY_APPOINTMENTS')->name('index');

    Route::get('/create', [AppointmentController::class, 'create'])
        ->middleware('permission:CREATE_APPOINTMENTS')->name('create');

    Route::post('/', [AppointmentController::class, 'store'])
        ->middleware('permission:CREATE_APPOINTMENTS')->name('store');

    Route::post('/bulk-delete', [AppointmentController::class, 'bulkDelete'])
        ->middleware('permission:BULK_DELETE_APPOINTMENTS')->name('bulk-delete');

    Route::post('/bulk-restore', [AppointmentController::class, 'bulkRestore'])
        ->middleware('permission:BULK_RESTORE_APPOINTMENTS')->name('bulk-restore');

    Route::get('/export', AppointmentExportController::class)
        ->middleware(['permission:EXPORT_APPOINTMENTS', 'throttle:10,1'])->name('export');

    Route::get('/{uuid}', [AppointmentController::class, 'show'])
        ->middleware('permission:VIEW_APPOINTMENTS')->whereUuid('uuid')->name('show');

    Route::get('/{uuid}/edit', [AppointmentController::class, 'edit'])
        ->middleware('permission:UPDATE_APPOINTMENTS')->whereUuid('uuid')->name('edit');

    Route::put('/{uuid}', [AppointmentController::class, 'update'])
        ->middleware('permission:UPDATE_APPOINTMENTS')->whereUuid('uuid')->name('update');

    Route::patch('/{uuid}/read', [AppointmentController::class, 'markRead'])
        ->middleware('permission:UPDATE_APPOINTMENTS')->whereUuid('uuid')->name('read');

    Route::patch('/{uuid}/confirm', [AppointmentController::class, 'confirm'])
        ->middleware('permission:UPDATE_APPOINTMENTS')->whereUuid('uuid')->name('confirm');

    Route::patch('/{uuid}/reschedule', [AppointmentController::class, 'reschedule'])
        ->middleware('permission:UPDATE_APPOINTMENTS')->whereUuid('uuid')->name('reschedule');

    Route::patch('/{uuid}/cancel', [AppointmentController::class, 'cancel'])
        ->middleware('permission:UPDATE_APPOINTMENTS')->whereUuid('uuid')->name('cancel');

    Route::post('/{uuid}/follow-up-calls', [AppointmentController::class, 'addFollowUpCall'])
        ->middleware('permission:UPDATE_APPOINTMENTS')->whereUuid('uuid')->name('follow-up-calls.store');

    Route::delete('/{uuid}', [AppointmentController::class, 'destroy'])
        ->middleware('permission:DELETE_APPOINTMENTS')->whereUuid('uuid')->name('destroy');

    Route::post('/{uuid}/restore', [AppointmentController::class, 'restore'])
        ->middleware('permission:RESTORE_APPOINTMENTS')->whereUuid('uuid')->name('restore');
});
