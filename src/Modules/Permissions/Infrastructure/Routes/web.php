<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Permissions\Infrastructure\Http\Controllers\Api\AdminPermissionController;
use Modules\Permissions\Infrastructure\Http\Controllers\Api\PermissionExportController;
use Modules\Permissions\Infrastructure\Http\Controllers\Web\PermissionPageController;

Route::get('/', [PermissionPageController::class, 'index'])->name('permissions.index')->middleware('permission:VIEW_PERMISSIONS');
Route::get('/create', [PermissionPageController::class, 'create'])->name('permissions.create')->middleware('permission:CREATE_PERMISSIONS');
Route::get('/{uuid}', [PermissionPageController::class, 'show'])->name('permissions.show')->whereUuid('uuid')->middleware('permission:VIEW_PERMISSIONS');
Route::get('/{uuid}/edit', [PermissionPageController::class, 'edit'])->name('permissions.edit')->whereUuid('uuid')->middleware('permission:UPDATE_PERMISSIONS');

Route::prefix('data')->group(function (): void {
    Route::prefix('admin')->group(function (): void {
        Route::get('/export', PermissionExportController::class)->name('permissions.data.export')->middleware('permission:VIEW_PERMISSIONS');
        Route::get('/', [AdminPermissionController::class, 'index'])->name('permissions.data.index')->middleware('permission:VIEW_PERMISSIONS');
        Route::post('/', [AdminPermissionController::class, 'store'])->name('permissions.data.store')->middleware('permission:CREATE_PERMISSIONS');
        Route::get('/{uuid}', [AdminPermissionController::class, 'show'])->name('permissions.data.show')->whereUuid('uuid')->middleware('permission:VIEW_PERMISSIONS');
        Route::put('/{uuid}', [AdminPermissionController::class, 'update'])->name('permissions.data.update')->whereUuid('uuid')->middleware('permission:UPDATE_PERMISSIONS');
        Route::delete('/{uuid}', [AdminPermissionController::class, 'destroy'])->name('permissions.data.destroy')->whereUuid('uuid')->middleware('permission:DELETE_PERMISSIONS');
    });
});
