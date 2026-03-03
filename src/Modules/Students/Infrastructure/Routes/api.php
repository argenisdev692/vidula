<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Students\Infrastructure\Http\Controllers\Api\StudentController;

/**
 * Student Context - API routes.
 */
Route::prefix('admin')->group(function () {
    Route::get('/', [StudentController::class, 'index'])->name('api.admin.student.index');
    Route::post('/', [StudentController::class, 'store'])->name('api.admin.student.store');
    Route::get('/export', [StudentController::class, 'export'])->name('api.admin.student.export');
    Route::get('/{uuid}', [StudentController::class, 'show'])->name('api.admin.student.show')->whereUuid('uuid');
    Route::put('/{uuid}', [StudentController::class, 'update'])->name('api.admin.student.update')->whereUuid('uuid');
    Route::delete('/{uuid}', [StudentController::class, 'destroy'])->name('api.admin.student.destroy')->whereUuid('uuid');
    Route::patch('/{uuid}/restore', [StudentController::class, 'restore'])->name('api.admin.student.restore')->whereUuid('uuid');
});

// For current user profile data
Route::get('/me', [StudentController::class, 'show'])->name('api.student.me');
Route::put('/me', [StudentController::class, 'update'])->name('api.student.me.update');
