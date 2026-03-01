<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Users\Infrastructure\Http\Controllers\Api\AdminUserController;
use Modules\Users\Infrastructure\Http\Controllers\Api\UserProfileController;
use Modules\Users\Infrastructure\Http\Controllers\Api\UserExportController;

/**
 * Users Context — API routes.
 *
 * Prefix: /api/users (applied by ServiceProvider).
 */
Route::middleware(['auth:sanctum'])->group(function () {

    // Admin Routes
    Route::middleware(['role:super-admin'])->prefix('admin')->group(function () {
        Route::get('/export', UserExportController::class)->name('api.admin.users.export');
        Route::get('/', [AdminUserController::class, 'index'])->name('api.admin.users.index');
        Route::get('/{uuid}', [AdminUserController::class, 'show'])->name('api.admin.users.show')->whereUuid('uuid');
        Route::post('/', [AdminUserController::class, 'store'])->name('api.admin.users.store');
        Route::put('/{uuid}', [AdminUserController::class, 'update'])->name('api.admin.users.update')->whereUuid('uuid');
        Route::delete('/{uuid}', [AdminUserController::class, 'destroy'])->name('api.admin.users.destroy')->whereUuid('uuid');
        Route::patch('/{uuid}/restore', [AdminUserController::class, 'restore'])->name('api.admin.users.restore')->whereUuid('uuid');
        Route::post('/{uuid}/suspend', [AdminUserController::class, 'suspend'])->name('api.admin.users.suspend')->whereUuid('uuid');
        Route::post('/{uuid}/activate', [AdminUserController::class, 'activate'])->name('api.admin.users.activate')->whereUuid('uuid');
    });

    // Profile Routes
    Route::prefix('profile')->group(function () {
        Route::get('/', [UserProfileController::class, 'show'])->name('api.users.profile.show');
        Route::put('/', [UserProfileController::class, 'update'])->name('api.users.profile.update');
    });

});
