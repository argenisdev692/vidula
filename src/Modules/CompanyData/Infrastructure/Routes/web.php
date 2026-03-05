<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Modules\CompanyData\Infrastructure\Http\Controllers\Api\CompanyDataController;
use Modules\CompanyData\Infrastructure\Http\Controllers\Api\CompanyDataExportController;

/**
 * CompanyData Context — Web routes (Inertia pages + JSON endpoints for React Query).
 *
 * Prefix: /company-data (applied by ServiceProvider).
 * Middleware: web, auth (applied by ServiceProvider).
 */

// ── Inertia Pages ──
Route::get('/', function () {
    return Inertia::render('company-data/CompanyDataIndexPage');
})->name('company-data.index')->middleware('permission:VIEW_COMPANY_DATA');

Route::get('/create', function () {
    return Inertia::render('company-data/CompanyDataCreatePage');
})->name('company-data.create')->middleware('permission:CREATE_COMPANY_DATA');

Route::get('/{uuid}', function (string $uuid) {
    return Inertia::render('company-data/CompanyDataShowPage', ['companyId' => $uuid]);
})->name('company-data.show')->whereUuid('uuid')->middleware('permission:VIEW_COMPANY_DATA');

Route::get('/{uuid}/edit', function (string $uuid) {
    return Inertia::render('company-data/CompanyDataEditPage', ['companyId' => $uuid]);
})->name('company-data.edit')->whereUuid('uuid')->middleware('permission:UPDATE_COMPANY_DATA');

// ── JSON Endpoints for React Query (Internal Web API) ──
Route::prefix('data')->group(function (): void {
    // Current user profile
    Route::get('/me', [CompanyDataController::class, 'show'])->name('company-data.data.me');
    Route::put('/me', [CompanyDataController::class, 'update'])->name('company-data.data.me.update');

    // Admin
    Route::prefix('admin')->group(function (): void {
        Route::get('/export', CompanyDataExportController::class)
            ->name('company-data.data.export')
            ->middleware('permission:VIEW_COMPANY_DATA');

        Route::get('/', [CompanyDataController::class, 'index'])
            ->name('company-data.data.index')
            ->middleware('permission:VIEW_COMPANY_DATA');

        Route::post('/', [CompanyDataController::class, 'store'])
            ->name('company-data.data.store')
            ->middleware('permission:CREATE_COMPANY_DATA');

        Route::get('/{uuid}', [CompanyDataController::class, 'show'])
            ->name('company-data.data.show')
            ->whereUuid('uuid')
            ->middleware('permission:VIEW_COMPANY_DATA');

        Route::put('/{uuid}', [CompanyDataController::class, 'update'])
            ->name('company-data.data.update')
            ->whereUuid('uuid')
            ->middleware('permission:UPDATE_COMPANY_DATA');

        Route::delete('/{uuid}', [CompanyDataController::class, 'destroy'])
            ->name('company-data.data.destroy')
            ->whereUuid('uuid')
            ->middleware('permission:DELETE_COMPANY_DATA');

        Route::patch('/{uuid}/restore', [CompanyDataController::class, 'restore'])
            ->name('company-data.data.restore')
            ->whereUuid('uuid')
            ->middleware('permission:DELETE_COMPANY_DATA');
    });
});
