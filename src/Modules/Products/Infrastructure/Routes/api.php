<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Products\Infrastructure\Http\Controllers\Api\ProductController;

/**
 * Product Context - API routes.
 */
Route::prefix('admin')->group(function () {
    Route::middleware('permission:VIEW_PRODUCTS')->group(function (): void {
        Route::get('/', [ProductController::class, 'index'])->name('api.admin.product.index');
        Route::get('/{uuid}', [ProductController::class, 'show'])->name('api.admin.product.show')->whereUuid('uuid');
    });

    Route::middleware('permission:CREATE_PRODUCTS')->group(function (): void {
        Route::post('/', [ProductController::class, 'store'])->name('api.admin.product.store');
    });

    Route::middleware('permission:UPDATE_PRODUCTS')->group(function (): void {
        Route::put('/{uuid}', [ProductController::class, 'update'])->name('api.admin.product.update')->whereUuid('uuid');
    });

    Route::middleware('permission:DELETE_PRODUCTS')->group(function (): void {
        Route::delete('/{uuid}', [ProductController::class, 'destroy'])->name('api.admin.product.destroy')->whereUuid('uuid');
    });

    Route::middleware('permission:RESTORE_PRODUCTS')->group(function (): void {
        Route::patch('/{uuid}/restore', [ProductController::class, 'restore'])->name('api.admin.product.restore')->whereUuid('uuid');
    });
});
