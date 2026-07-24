<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Clients\Infrastructure\Http\Controllers\ClientController;
use Modules\Clients\Infrastructure\Http\Controllers\ClientExportController;

/*
| Clients module — web (session + Inertia).
|
| Static segments BEFORE `{uuid}` so bulk/export are never captured as UUIDs.
*/
Route::middleware(['web', 'auth', 'throttle:60,1'])->prefix('clients')->name('clients.')->group(function (): void {
    Route::get('/', [ClientController::class, 'index'])
        ->middleware('permission:VIEW_ANY_CLIENTS')->name('index');

    Route::post('/', [ClientController::class, 'store'])
        ->middleware('permission:CREATE_CLIENTS')->name('store');

    Route::post('/bulk-delete', [ClientController::class, 'bulkDelete'])
        ->middleware('permission:BULK_DELETE_CLIENTS')->name('bulk-delete');

    Route::post('/bulk-restore', [ClientController::class, 'bulkRestore'])
        ->middleware('permission:BULK_RESTORE_CLIENTS')->name('bulk-restore');

    Route::get('/export', ClientExportController::class)
        ->middleware(['permission:EXPORT_CLIENTS', 'throttle:10,1'])->name('export');

    Route::get('/{uuid}', [ClientController::class, 'show'])
        ->middleware('permission:VIEW_CLIENTS')->whereUuid('uuid')->name('show');

    Route::put('/{uuid}', [ClientController::class, 'update'])
        ->middleware('permission:UPDATE_CLIENTS')->whereUuid('uuid')->name('update');

    Route::delete('/{uuid}', [ClientController::class, 'destroy'])
        ->middleware('permission:DELETE_CLIENTS')->whereUuid('uuid')->name('destroy');

    Route::post('/{uuid}/restore', [ClientController::class, 'restore'])
        ->middleware('permission:RESTORE_CLIENTS')->whereUuid('uuid')->name('restore');
});
