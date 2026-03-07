<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Permissions\Infrastructure\Http\Controllers\Api\AdminPermissionController;
use Modules\Permissions\Infrastructure\Http\Controllers\Api\PermissionExportController;

Route::middleware(['auth:sanctum'])->group(function (): void {
    Route::prefix('admin')->group(function (): void {
        Route::get('/export', PermissionExportController::class)->name('api.admin.permissions.export')->middleware('permission:VIEW_PERMISSIONS');
        Route::get('/', [AdminPermissionController::class, 'index'])->name('api.admin.permissions.index')->middleware('permission:VIEW_PERMISSIONS');
        Route::post('/', [AdminPermissionController::class, 'store'])->name('api.admin.permissions.store')->middleware('permission:CREATE_PERMISSIONS');
        Route::get('/{uuid}', [AdminPermissionController::class, 'show'])->name('api.admin.permissions.show')->whereUuid('uuid')->middleware('permission:VIEW_PERMISSIONS');
        Route::put('/{uuid}', [AdminPermissionController::class, 'update'])->name('api.admin.permissions.update')->whereUuid('uuid')->middleware('permission:UPDATE_PERMISSIONS');
        Route::delete('/{uuid}', [AdminPermissionController::class, 'destroy'])->name('api.admin.permissions.destroy')->whereUuid('uuid')->middleware('permission:DELETE_PERMISSIONS');
    });
});
