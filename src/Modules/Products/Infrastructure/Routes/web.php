<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Modules\Product\Infrastructure\Http\Controllers\Api\ProductController;

/**
 * Product Context — Web routes (Inertia pages + JSON endpoints for React Query).
 *
 * Prefix: /product (applied by ServiceProvider).
 * Middleware: web, auth (applied by ServiceProvider).
 */

// ── Inertia Pages ──
Route::get('/', function () {
    return Inertia::render('product/ProductIndexPage');
})->name('product.index');

Route::get('/create', function () {
    return Inertia::render('product/ProductCreatePage');
})->name('product.create');

Route::get('/{uuid}', function (string $uuid) {
    return Inertia::render('product/ProductShowPage', ['companyId' => $uuid]);
})->name('product.show')->whereUuid('uuid');

Route::get('/{uuid}/edit', function (string $uuid) {
    return Inertia::render('product/ProductEditPage', ['companyId' => $uuid]);
})->name('product.edit')->whereUuid('uuid');

// ── JSON Endpoints for React Query (Internal Web API) ──
// These endpoints are used by the frontend React components via React Query
Route::prefix('data')->group(function () {
    // Current user profile
    Route::get('/me', [ProductController::class, 'show'])->name('product.data.me');
    Route::put('/me', [ProductController::class, 'update'])->name('product.data.me.update');

    // Admin
    Route::prefix('admin')->group(function () {
        Route::get('/export', [ProductController::class, 'export'])->name('product.data.export');
        Route::get('/', [ProductController::class, 'index'])->name('product.data.index');
        Route::post('/', [ProductController::class, 'store'])->name('product.data.store');
        Route::get('/{uuid}', [ProductController::class, 'show'])->name('product.data.show')->whereUuid('uuid');
        Route::put('/{uuid}', [ProductController::class, 'update'])->name('product.data.update')->whereUuid('uuid');
        Route::delete('/{uuid}', [ProductController::class, 'destroy'])->name('product.data.destroy')->whereUuid('uuid');
        Route::patch('/{uuid}/restore', [ProductController::class, 'restore'])->name('product.data.restore')->whereUuid('uuid');
        Route::post('/bulk-delete', [ProductController::class, 'bulkDelete'])->name('product.data.bulk-delete');
    });
});
