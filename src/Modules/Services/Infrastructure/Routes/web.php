<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Services\Infrastructure\Http\Controllers\ServiceController;

/*
| Services module — web (session + Inertia).
|
| Management routes are gated by `permission:*_SERVICES` (UI authz uses
| permissions, never roles). Static segments are declared BEFORE the `{uuid}`
| wildcard so `/bulk-delete` and `/bulk-restore` are never captured as a UUID.
*/
Route::middleware(['web', 'auth', 'throttle:60,1'])->prefix('services')->name('services.')->group(function (): void {
    Route::get('/', [ServiceController::class, 'index'])
        ->middleware('permission:VIEW_ANY_SERVICES')->name('index');

    Route::post('/', [ServiceController::class, 'store'])
        ->middleware('permission:CREATE_SERVICES')->name('store');

    Route::post('/bulk-delete', [ServiceController::class, 'bulkDelete'])
        ->middleware('permission:BULK_DELETE_SERVICES')->name('bulk-delete');

    Route::post('/bulk-restore', [ServiceController::class, 'bulkRestore'])
        ->middleware('permission:BULK_RESTORE_SERVICES')->name('bulk-restore');

    Route::get('/{uuid}', [ServiceController::class, 'show'])
        ->middleware('permission:VIEW_SERVICES')->whereUuid('uuid')->name('show');

    Route::put('/{uuid}', [ServiceController::class, 'update'])
        ->middleware('permission:UPDATE_SERVICES')->whereUuid('uuid')->name('update');

    Route::delete('/{uuid}', [ServiceController::class, 'destroy'])
        ->middleware('permission:DELETE_SERVICES')->whereUuid('uuid')->name('destroy');

    Route::post('/{uuid}/restore', [ServiceController::class, 'restore'])
        ->middleware('permission:RESTORE_SERVICES')->whereUuid('uuid')->name('restore');
});
