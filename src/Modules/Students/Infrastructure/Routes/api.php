<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Students\Infrastructure\Http\Controllers\Api\StudentController;
use Modules\Students\Infrastructure\Http\Controllers\Api\StudentExportController;

/**
 * Student Context - API routes.
 */
Route::prefix('admin')->group(function () {
    Route::get('/', [StudentController::class, 'index'])->name('api.admin.student.index')->middleware('permission:VIEW_STUDENTS');
    Route::post('/', [StudentController::class, 'store'])->name('api.admin.student.store')->middleware('permission:CREATE_STUDENTS');
    Route::get('/export', [StudentExportController::class, '__invoke'])->name('api.admin.student.export')->middleware('permission:VIEW_STUDENTS');
    Route::get('/{uuid}', [StudentController::class, 'show'])->name('api.admin.student.show')->whereUuid('uuid')->middleware('permission:VIEW_STUDENTS');
    Route::put('/{uuid}', [StudentController::class, 'update'])->name('api.admin.student.update')->whereUuid('uuid')->middleware('permission:UPDATE_STUDENTS');
    Route::delete('/{uuid}', [StudentController::class, 'destroy'])->name('api.admin.student.destroy')->whereUuid('uuid')->middleware('permission:DELETE_STUDENTS');
    Route::patch('/{uuid}/restore', [StudentController::class, 'restore'])->name('api.admin.student.restore')->whereUuid('uuid')->middleware('permission:DELETE_STUDENTS');
});

// For current user profile data
Route::get('/me', [StudentController::class, 'show'])->name('api.student.me');
Route::put('/me', [StudentController::class, 'update'])->name('api.student.me.update');
