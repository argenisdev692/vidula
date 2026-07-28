<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Products\Infrastructure\Http\Controllers\Api\ProductApiController;

/*
|| Products API — Sanctum secondary surface (read lookup for Scramble / mobile).
*/
Route::middleware(['auth:sanctum', 'throttle:60,1'])
    ->prefix('products')
    ->name('api.products.')
    ->group(function (): void {
        Route::get('/', [ProductApiController::class, 'index'])
            ->middleware('permission:VIEW_ANY_PRODUCTS')
            ->name('index');
        Route::get('/{uuid}', [ProductApiController::class, 'show'])
            ->middleware('permission:VIEW_PRODUCTS')
            ->whereUuid('uuid')
            ->name('show');
    });
