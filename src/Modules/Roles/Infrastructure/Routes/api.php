<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Roles\Infrastructure\Http\Controllers\Api\AdminRoleController;
use Modules\Roles\Infrastructure\Http\Controllers\Api\RoleExportController;

Route::middleware(['auth:sanctum'])->group(function (): void {
    Route::prefix('admin')->group(function (): void {
        Route::get('/export', RoleExportController::class)->name('api.admin.roles.export')->middleware('permission:VIEW_ROLES');
        Route::get('/', [AdminRoleController::class, 'index'])->name('api.admin.roles.index')->middleware('permission:VIEW_ROLES');
        Route::post('/', [AdminRoleController::class, 'store'])->name('api.admin.roles.store')->middleware('permission:CREATE_ROLES');
        Route::get('/{uuid}', [AdminRoleController::class, 'show'])->name('api.admin.roles.show')->whereUuid('uuid')->middleware('permission:VIEW_ROLES');
        Route::put('/{uuid}', [AdminRoleController::class, 'update'])->name('api.admin.roles.update')->whereUuid('uuid')->middleware('permission:UPDATE_ROLES');
        Route::delete('/{uuid}', [AdminRoleController::class, 'destroy'])->name('api.admin.roles.destroy')->whereUuid('uuid')->middleware('permission:DELETE_ROLES');
    });
});
