<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Modules\CompanyData\Infrastructure\Http\Controllers\Api\CompanyDataController;

/**
 * CompanyData Context — Web routes (Inertia pages + JSON endpoints for React Query).
 *
 * Prefix: /company-data (applied by ServiceProvider).
 * Middleware: web, auth (applied by ServiceProvider).
 */

// ── Inertia Pages ──
Route::get('/', function () {
    return Inertia::render('company-data/CompanyDataIndexPage');
})->name('company-data.index');

Route::get('/create', function () {
    return Inertia::render('company-data/CompanyDataCreatePage');
})->name('company-data.create');

Route::get('/{uuid}', function (string $uuid) {
    return Inertia::render('company-data/CompanyDataShowPage', ['companyId' => $uuid]);
})->name('company-data.show')->whereUuid('uuid');

Route::get('/{uuid}/edit', function (string $uuid) {
    return Inertia::render('company-data/CompanyDataEditPage', ['companyId' => $uuid]);
})->name('company-data.edit')->whereUuid('uuid');

// ── JSON Endpoints for React Query (Internal Web API) ──
// These endpoints are used by the frontend React components via React Query
Route::prefix('data')->group(function () {
    // Current user profile
    Route::get('/me', [CompanyDataController::class, 'show'])->name('company-data.data.me');
    Route::put('/me', [CompanyDataController::class, 'update'])->name('company-data.data.me.update');

    // Admin
    Route::prefix('admin')->group(function () {
        Route::get('/export', [CompanyDataController::class, 'export'])->name('company-data.data.export');
        Route::get('/', [CompanyDataController::class, 'index'])->name('company-data.data.index');
        Route::post('/', [CompanyDataController::class, 'store'])->name('company-data.data.store');
        Route::get('/{uuid}', [CompanyDataController::class, 'show'])->name('company-data.data.show')->whereUuid('uuid');
        Route::put('/{uuid}', [CompanyDataController::class, 'update'])->name('company-data.data.update')->whereUuid('uuid');
        Route::delete('/{uuid}', [CompanyDataController::class, 'destroy'])->name('company-data.data.destroy')->whereUuid('uuid');
        Route::patch('/{uuid}/restore', [CompanyDataController::class, 'restore'])->name('company-data.data.restore')->whereUuid('uuid');
    });
});
