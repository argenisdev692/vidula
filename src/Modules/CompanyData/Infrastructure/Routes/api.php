<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\CompanyData\Infrastructure\Http\Controllers\Api\CompanyDataController;
use Modules\CompanyData\Infrastructure\Http\Controllers\Api\CompanyDataExportController;

/**
 * CompanyData Context — API routes (Sanctum — mobile/external).
 */
Route::prefix('admin')->group(function (): void {
    Route::get('/export', CompanyDataExportController::class)
        ->name('api.admin.company_data.export')
        ->middleware('permission:VIEW_COMPANY_DATA');

    Route::get('/', [CompanyDataController::class, 'index'])
        ->name('api.admin.company_data.index')
        ->middleware('permission:VIEW_COMPANY_DATA');

    Route::post('/', [CompanyDataController::class, 'store'])
        ->name('api.admin.company_data.store')
        ->middleware('permission:CREATE_COMPANY_DATA');

    Route::get('/{uuid}', [CompanyDataController::class, 'show'])
        ->name('api.admin.company_data.show')
        ->whereUuid('uuid')
        ->middleware('permission:VIEW_COMPANY_DATA');

    Route::put('/{uuid}', [CompanyDataController::class, 'update'])
        ->name('api.admin.company_data.update')
        ->whereUuid('uuid')
        ->middleware('permission:UPDATE_COMPANY_DATA');

    Route::delete('/{uuid}', [CompanyDataController::class, 'destroy'])
        ->name('api.admin.company_data.destroy')
        ->whereUuid('uuid')
        ->middleware('permission:DELETE_COMPANY_DATA');

    Route::patch('/{uuid}/restore', [CompanyDataController::class, 'restore'])
        ->name('api.admin.company_data.restore')
        ->whereUuid('uuid')
        ->middleware('permission:DELETE_COMPANY_DATA');
});

// For current user profile data
Route::get('/me', [CompanyDataController::class, 'show'])->name('api.company_data.me');
Route::put('/me', [CompanyDataController::class, 'update'])->name('api.company_data.me.update');
