<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Portfolio\Infrastructure\Http\Controllers\PortfolioController;
use Modules\Portfolio\Infrastructure\Http\Controllers\PortfolioGalleryController;

/*
| Portfolio module — web (session + Inertia).
|
| Management routes are gated by `permission:*_PORTFOLIOS` (UI authz uses
| permissions, never roles). Static segments are declared BEFORE the `{uuid}`
| wildcard so `/bulk-delete` and `/bulk-restore` are never captured as a UUID.
| Gallery routes are nested under `/{uuid}/gallery` and share
| `permission:UPDATE_PORTFOLIOS` with the parent aggregate.
*/
Route::middleware(['web', 'auth', 'throttle:60,1'])->prefix('portfolios')->name('portfolios.')->group(function (): void {
    Route::get('/', [PortfolioController::class, 'index'])
        ->middleware('permission:VIEW_ANY_PORTFOLIOS')->name('index');

    Route::post('/', [PortfolioController::class, 'store'])
        ->middleware('permission:CREATE_PORTFOLIOS')->name('store');

    Route::post('/bulk-delete', [PortfolioController::class, 'bulkDelete'])
        ->middleware('permission:BULK_DELETE_PORTFOLIOS')->name('bulk-delete');

    Route::post('/bulk-restore', [PortfolioController::class, 'bulkRestore'])
        ->middleware('permission:BULK_RESTORE_PORTFOLIOS')->name('bulk-restore');

    Route::get('/{uuid}', [PortfolioController::class, 'show'])
        ->middleware('permission:VIEW_PORTFOLIOS')->whereUuid('uuid')->name('show');

    Route::put('/{uuid}', [PortfolioController::class, 'update'])
        ->middleware('permission:UPDATE_PORTFOLIOS')->whereUuid('uuid')->name('update');

    Route::delete('/{uuid}', [PortfolioController::class, 'destroy'])
        ->middleware('permission:DELETE_PORTFOLIOS')->whereUuid('uuid')->name('destroy');

    Route::post('/{uuid}/restore', [PortfolioController::class, 'restore'])
        ->middleware('permission:RESTORE_PORTFOLIOS')->whereUuid('uuid')->name('restore');

    Route::post('/{uuid}/gallery', [PortfolioGalleryController::class, 'store'])
        ->middleware('permission:UPDATE_PORTFOLIOS')->whereUuid('uuid')->name('gallery.store');

    Route::delete('/{uuid}/gallery/{mediaUuid}', [PortfolioGalleryController::class, 'destroy'])
        ->middleware('permission:UPDATE_PORTFOLIOS')->whereUuid('uuid')->whereUuid('mediaUuid')->name('gallery.destroy');

    Route::post('/{uuid}/gallery/reorder', [PortfolioGalleryController::class, 'reorder'])
        ->middleware('permission:UPDATE_PORTFOLIOS')->whereUuid('uuid')->name('gallery.reorder');
});
