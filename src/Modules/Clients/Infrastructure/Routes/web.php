<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Modules\Client\Infrastructure\Http\Controllers\Api\ClientController;

/**
 * Client Context — Web routes (Inertia pages + JSON endpoints for React Query).
 *
 * Prefix: /client (applied by ServiceProvider).
 * Middleware: web, auth (applied by ServiceProvider).
 */

// ── Inertia Pages ──
Route::get('/', function () {
    return Inertia::render('client/ClientIndexPage');
})->name('client.index');

Route::get('/create', function () {
    return Inertia::render('client/ClientCreatePage');
})->name('client.create');

Route::get('/{uuid}', function (string $uuid) {
    return Inertia::render('client/ClientShowPage', ['companyId' => $uuid]);
})->name('client.show')->whereUuid('uuid');

Route::get('/{uuid}/edit', function (string $uuid) {
    return Inertia::render('client/ClientEditPage', ['companyId' => $uuid]);
})->name('client.edit')->whereUuid('uuid');

// ── JSON Endpoints for React Query (Internal Web API) ──
// These endpoints are used by the frontend React components via React Query
Route::prefix('data')->group(function () {
    // Current user profile
    Route::get('/me', [ClientController::class, 'show'])->name('client.data.me');
    Route::put('/me', [ClientController::class, 'update'])->name('client.data.me.update');

    // Admin
    Route::prefix('admin')->group(function () {
        Route::get('/export', [ClientController::class, 'export'])->name('client.data.export');
        Route::get('/', [ClientController::class, 'index'])->name('client.data.index');
        Route::post('/', [ClientController::class, 'store'])->name('client.data.store');
        Route::get('/{uuid}', [ClientController::class, 'show'])->name('client.data.show')->whereUuid('uuid');
        Route::put('/{uuid}', [ClientController::class, 'update'])->name('client.data.update')->whereUuid('uuid');
        Route::delete('/{uuid}', [ClientController::class, 'destroy'])->name('client.data.destroy')->whereUuid('uuid');
        Route::patch('/{uuid}/restore', [ClientController::class, 'restore'])->name('client.data.restore')->whereUuid('uuid');
        Route::post('/bulk-delete', [ClientController::class, 'bulkDelete'])->name('client.data.bulk-delete');
    });
});
