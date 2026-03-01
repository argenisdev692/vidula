<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Users\Infrastructure\Http\Controllers\Web\UserPageController;

use Modules\Users\Infrastructure\Http\Controllers\Api\AdminUserController;
use Modules\Users\Infrastructure\Http\Controllers\Api\UserProfileController;
use Modules\Users\Infrastructure\Http\Controllers\Api\UserExportController;

/**
 * Users Context — Web routes (Inertia pages + JSON endpoints for React Query).
 *
 * Prefix: /users (applied by ServiceProvider).
 * Middleware: web, auth (applied by ServiceProvider).
 */

// ── Inertia Pages ──
Route::get('/', [UserPageController::class, 'index'])->name('users.index');
Route::get('/create', [UserPageController::class, 'create'])->name('users.create');
Route::get('/{uuid}', [UserPageController::class, 'show'])->name('users.show')->whereUuid('uuid');
Route::get('/{uuid}/edit', [UserPageController::class, 'edit'])->name('users.edit')->whereUuid('uuid');
Route::delete('/{uuid}', [UserPageController::class, 'destroy'])->name('users.destroy')->whereUuid('uuid');
Route::patch('/{uuid}/restore', [UserPageController::class, 'restore'])->name('users.restore')->whereUuid('uuid');

// ── JSON Endpoints for React Query (Internal Web API) ──
// These endpoints are used by the frontend React components via React Query
Route::prefix('data')->group(function () {
    // Admin
    Route::middleware(['role:SUPER_ADMIN'])->prefix('admin')->group(function () {
        Route::get('/export', UserExportController::class)->name('users.data.export');
        Route::get('/', [AdminUserController::class, 'index'])->name('users.data.index');
        Route::post('/', [AdminUserController::class, 'store'])->name('users.data.store');
        Route::get('/{uuid}', [AdminUserController::class, 'show'])->name('users.data.show')->whereUuid('uuid');
        Route::put('/{uuid}', [AdminUserController::class, 'update'])->name('users.data.update')->whereUuid('uuid');
        Route::delete('/{uuid}', [AdminUserController::class, 'destroy'])->name('users.data.destroy')->whereUuid('uuid');
        Route::patch('/{uuid}/restore', [AdminUserController::class, 'restore'])->name('users.data.restore')->whereUuid('uuid');
        Route::post('/{uuid}/suspend', [AdminUserController::class, 'suspend'])->name('users.data.suspend')->whereUuid('uuid');
        Route::post('/{uuid}/activate', [AdminUserController::class, 'activate'])->name('users.data.activate')->whereUuid('uuid');
    });

    // Profile
    Route::prefix('profile')->group(function () {
        Route::get('/', [UserProfileController::class, 'show'])->name('users.data.profile.show');
        Route::put('/', [UserProfileController::class, 'update'])->name('users.data.profile.update');
    });
});
