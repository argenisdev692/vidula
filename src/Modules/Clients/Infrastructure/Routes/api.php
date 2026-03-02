<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Client\Infrastructure\Http\Controllers\Api\ClientController;

/**
 * Client Context — API routes.
 */
Route::prefix('admin')->group(function () {
    Route::get('/', [ClientController::class, 'index'])->name('api.admin.client.index');
    Route::post('/', [ClientController::class, 'store'])->name('api.admin.client.store');
    Route::get('/{uuid}', [ClientController::class, 'show'])->name('api.admin.client.show')->whereUuid('uuid');
    Route::put('/{uuid}', [ClientController::class, 'update'])->name('api.admin.client.update')->whereUuid('uuid');
    Route::delete('/{uuid}', [ClientController::class, 'destroy'])->name('api.admin.client.destroy')->whereUuid('uuid');
    Route::patch('/{uuid}/restore', [ClientController::class, 'restore'])->name('api.admin.client.restore')->whereUuid('uuid');
});

// For current user profile data
Route::get('/me', [ClientController::class, 'show'])->name('api.client.me');
Route::put('/me', [ClientController::class, 'update'])->name('api.client.me.update');
