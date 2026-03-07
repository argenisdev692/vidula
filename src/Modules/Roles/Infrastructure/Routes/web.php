<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Roles\Infrastructure\Http\Controllers\Api\AdminRoleController;
use Modules\Roles\Infrastructure\Http\Controllers\Api\RoleExportController;
use Modules\Roles\Infrastructure\Http\Controllers\Web\RolePageController;

Route::get('/', [RolePageController::class, 'index'])->name('roles.index')->middleware('permission:VIEW_ROLES');
Route::get('/create', [RolePageController::class, 'create'])->name('roles.create')->middleware('permission:CREATE_ROLES');
Route::get('/{uuid}', [RolePageController::class, 'show'])->name('roles.show')->whereUuid('uuid')->middleware('permission:VIEW_ROLES');
Route::get('/{uuid}/edit', [RolePageController::class, 'edit'])->name('roles.edit')->whereUuid('uuid')->middleware('permission:UPDATE_ROLES');

Route::prefix('data')->group(function (): void {
    Route::prefix('admin')->group(function (): void {
        Route::get('/export', RoleExportController::class)->name('roles.data.export')->middleware('permission:VIEW_ROLES');
        Route::get('/', [AdminRoleController::class, 'index'])->name('roles.data.index')->middleware('permission:VIEW_ROLES');
        Route::post('/', [AdminRoleController::class, 'store'])->name('roles.data.store')->middleware('permission:CREATE_ROLES');
        Route::get('/{uuid}', [AdminRoleController::class, 'show'])->name('roles.data.show')->whereUuid('uuid')->middleware('permission:VIEW_ROLES');
        Route::put('/{uuid}', [AdminRoleController::class, 'update'])->name('roles.data.update')->whereUuid('uuid')->middleware('permission:UPDATE_ROLES');
        Route::delete('/{uuid}', [AdminRoleController::class, 'destroy'])->name('roles.data.destroy')->whereUuid('uuid')->middleware('permission:DELETE_ROLES');
    });
});
