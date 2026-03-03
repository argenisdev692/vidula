<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Products\Infrastructure\Http\Controllers\Api\ProductController;
use Modules\Products\Infrastructure\Http\Controllers\Web\ProductPageController;
use Modules\Products\Infrastructure\Http\Controllers\Web\ProductExportController;

/**
 * Product Context — Web routes (Inertia pages + JSON endpoints for React Query).
 *
 * Prefix: /product (applied by ServiceProvider).
 * Middleware: web, auth (applied by ServiceProvider).
 */

// ── Inertia Pages ──
Route::get('/', [ProductPageController::class, 'index'])->name('product.index');
Route::get('/create', [ProductPageController::class, 'create'])->name('product.create');
Route::get('/{uuid}', [ProductPageController::class, 'show'])->name('product.show')->whereUuid('uuid');
Route::get('/{uuid}/edit', [ProductPageController::class, 'edit'])->name('product.edit')->whereUuid('uuid');

// ── JSON Endpoints for React Query (Internal Web API) ──
// These endpoints are used by the frontend React components via React Query
Route::prefix('data')->group(function () {
    // Current user profile
    Route::get('/me', [ProductController::class, 'show'])->name('product.data.me');
    Route::put('/me', [ProductController::class, 'update'])->name('product.data.me.update');

    // Admin
    Route::prefix('admin')->group(function () {
        Route::get('/export', [ProductExportController::class, '__invoke'])->name('product.data.export'); // MUST be before /{uuid}
        Route::get('/', [ProductController::class, 'index'])->name('product.data.index');
        Route::post('/', [ProductController::class, 'store'])->name('product.data.store');
        Route::post('/bulk-delete', [ProductController::class, 'bulkDelete'])->name('product.data.bulk-delete');
        Route::get('/{uuid}', [ProductController::class, 'show'])->name('product.data.show')->whereUuid('uuid');
        Route::put('/{uuid}', [ProductController::class, 'update'])->name('product.data.update')->whereUuid('uuid');
        Route::delete('/{uuid}', [ProductController::class, 'destroy'])->name('product.data.destroy')->whereUuid('uuid');
        Route::patch('/{uuid}/restore', [ProductController::class, 'restore'])->name('product.data.restore')->whereUuid('uuid');
    });
});
