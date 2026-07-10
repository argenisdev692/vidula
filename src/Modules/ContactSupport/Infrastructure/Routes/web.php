<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\ContactSupport\Infrastructure\Http\Controllers\ContactSupportController;
use Modules\ContactSupport\Infrastructure\Http\Controllers\ContactSupportExportController;

/*
| Contact-support module — web (session + Inertia).
|
| Management routes are gated by `permission:*_CONTACT_SUPPORTS` (UI authz uses
| permissions, never roles). Static segments are declared BEFORE the `{uuid}`
| wildcard so `/bulk-delete` and `/bulk-restore` are never captured as a UUID.
*/
Route::middleware(['web', 'auth', 'throttle:60,1'])->prefix('contact-supports')->name('contact-supports.')->group(function (): void {
    Route::get('/', [ContactSupportController::class, 'index'])
        ->middleware('permission:VIEW_ANY_CONTACT_SUPPORTS')->name('index');

    Route::post('/', [ContactSupportController::class, 'store'])
        ->middleware('permission:CREATE_CONTACT_SUPPORTS')->name('store');

    Route::post('/bulk-delete', [ContactSupportController::class, 'bulkDelete'])
        ->middleware('permission:BULK_DELETE_CONTACT_SUPPORTS')->name('bulk-delete');

    Route::post('/bulk-restore', [ContactSupportController::class, 'bulkRestore'])
        ->middleware('permission:BULK_RESTORE_CONTACT_SUPPORTS')->name('bulk-restore');

    Route::get('/export', ContactSupportExportController::class)
        ->middleware(['permission:EXPORT_CONTACT_SUPPORTS', 'throttle:10,1'])->name('export');

    Route::get('/{uuid}', [ContactSupportController::class, 'show'])
        ->middleware('permission:VIEW_CONTACT_SUPPORTS')->whereUuid('uuid')->name('show');

    Route::put('/{uuid}', [ContactSupportController::class, 'update'])
        ->middleware('permission:UPDATE_CONTACT_SUPPORTS')->whereUuid('uuid')->name('update');

    Route::patch('/{uuid}/read', [ContactSupportController::class, 'markRead'])
        ->middleware('permission:UPDATE_CONTACT_SUPPORTS')->whereUuid('uuid')->name('read');

    Route::delete('/{uuid}', [ContactSupportController::class, 'destroy'])
        ->middleware('permission:DELETE_CONTACT_SUPPORTS')->whereUuid('uuid')->name('destroy');

    Route::post('/{uuid}/restore', [ContactSupportController::class, 'restore'])
        ->middleware('permission:RESTORE_CONTACT_SUPPORTS')->whereUuid('uuid')->name('restore');
});
