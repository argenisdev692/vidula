<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Meeting\Infrastructure\Http\Controllers\GoogleCalendarOAuthController;
use Modules\Meeting\Infrastructure\Http\Controllers\MeetingAttendeeSearchController;
use Modules\Meeting\Infrastructure\Http\Controllers\MeetingAvailabilityController;
use Modules\Meeting\Infrastructure\Http\Controllers\MeetingCalendarController;
use Modules\Meeting\Infrastructure\Http\Controllers\MeetingController;
use Modules\Meeting\Infrastructure\Http\Controllers\MeetingExportController;
use Modules\Meeting\Infrastructure\Http\Controllers\MeetingQuickCreateLeadController;

/*
| Internal meeting scheduling — web (session + Inertia). Gated by
| `permission:*_MEETINGS` (UI authz uses permissions, never roles). Static
| segments (`/export`, `/calendar-feed`, `/attendees/search`, `/bulk-*`) are
| declared BEFORE the `{uuid}` wildcard so they are never captured as a UUID
| — mirrors Appointment's web.php.
*/
Route::middleware(['web', 'auth', 'throttle:60,1'])->prefix('meetings')->name('meetings.')->group(function (): void {
    Route::get('/', [MeetingController::class, 'index'])
        ->middleware('permission:VIEW_ANY_MEETINGS')->name('index');

    Route::get('/create', [MeetingController::class, 'create'])
        ->middleware('permission:CREATE_MEETINGS')->name('create');

    Route::post('/', [MeetingController::class, 'store'])
        ->middleware('permission:CREATE_MEETINGS')->name('store');

    Route::post('/bulk-delete', [MeetingController::class, 'bulkDelete'])
        ->middleware('permission:BULK_DELETE_MEETINGS')->name('bulk-delete');

    Route::post('/bulk-restore', [MeetingController::class, 'bulkRestore'])
        ->middleware('permission:BULK_RESTORE_MEETINGS')->name('bulk-restore');

    Route::get('/export', MeetingExportController::class)
        ->middleware(['permission:EXPORT_MEETINGS', 'throttle:10,1'])->name('export');

    Route::get('/calendar-feed', MeetingCalendarController::class)
        ->middleware('permission:VIEW_ANY_MEETINGS')->name('calendar-feed');

    Route::get('/attendees/search', MeetingAttendeeSearchController::class)
        ->middleware('permission:CREATE_MEETINGS|UPDATE_MEETINGS')->name('attendees.search');

    Route::post('/attendees/quick-lead', MeetingQuickCreateLeadController::class)
        ->middleware('permission:CREATE_MEETINGS|UPDATE_MEETINGS')->name('attendees.quick-lead');

    Route::get('/availability', MeetingAvailabilityController::class)
        ->middleware('permission:CREATE_MEETINGS|UPDATE_MEETINGS')->name('availability');

    Route::get('/{uuid}', [MeetingController::class, 'show'])
        ->middleware('permission:VIEW_MEETINGS')->whereUuid('uuid')->name('show');

    Route::get('/{uuid}/edit', [MeetingController::class, 'edit'])
        ->middleware('permission:UPDATE_MEETINGS')->whereUuid('uuid')->name('edit');

    Route::put('/{uuid}', [MeetingController::class, 'update'])
        ->middleware('permission:UPDATE_MEETINGS')->whereUuid('uuid')->name('update');

    Route::patch('/{uuid}/cancel', [MeetingController::class, 'cancel'])
        ->middleware('permission:UPDATE_MEETINGS')->whereUuid('uuid')->name('cancel');

    Route::delete('/{uuid}', [MeetingController::class, 'destroy'])
        ->middleware('permission:DELETE_MEETINGS')->whereUuid('uuid')->name('destroy');

    Route::post('/{uuid}/restore', [MeetingController::class, 'restore'])
        ->middleware('permission:RESTORE_MEETINGS')->whereUuid('uuid')->name('restore');
});

Route::middleware(['web', 'auth'])->prefix('google-calendar/oauth')->name('google-calendar.oauth.')->group(function (): void {
    Route::get('/connect', [GoogleCalendarOAuthController::class, 'connect'])->name('connect');
    Route::get('/callback', [GoogleCalendarOAuthController::class, 'callback'])->name('callback');
});
