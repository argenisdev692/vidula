<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Product\Infrastructure\Http\Controllers\Api\ProductController;

/**
 * Product Context — API routes.
 */
Route::prefix('admin')->group(function () {
    Route::get('/', [ProductController::class, 'index'])->name('api.admin.product.index');
    Route::post('/', [ProductController::class, 'store'])->name('api.admin.product.store');
    Route::get('/{uuid}', [ProductController::class, 'show'])->name('api.admin.product.show')->whereUuid('uuid');
    Route::put('/{uuid}', [ProductController::class, 'update'])->name('api.admin.product.update')->whereUuid('uuid');
    Route::delete('/{uuid}', [ProductController::class, 'destroy'])->name('api.admin.product.destroy')->whereUuid('uuid');
    Route::patch('/{uuid}/restore', [ProductController::class, 'restore'])->name('api.admin.product.restore')->whereUuid('uuid');
});

// For current user profile data
Route::get('/me', [ProductController::class, 'show'])->name('api.product.me');
Route::put('/me', [ProductController::class, 'update'])->name('api.product.me.update');
