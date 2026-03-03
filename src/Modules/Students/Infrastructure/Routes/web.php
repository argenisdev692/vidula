<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Students\Infrastructure\Http\Controllers\Api\StudentController;
use Modules\Students\Infrastructure\Http\Controllers\Api\StudentExportController;
use Modules\Students\Infrastructure\Http\Controllers\Web\StudentPageController;

/**
 * Student Context — Web routes (Inertia pages + JSON endpoints for React Query).
 *
 * Prefix: /student (applied by ServiceProvider).
 * Middleware: web, auth (applied by ServiceProvider).
 */

// ── Inertia Pages ──
Route::get('/', [StudentPageController::class, 'index'])->name('student.index');
Route::get('/create', [StudentPageController::class, 'create'])->name('student.create');
Route::get('/{uuid}', [StudentPageController::class, 'show'])->name('student.show')->whereUuid('uuid');
Route::get('/{uuid}/edit', [StudentPageController::class, 'edit'])->name('student.edit')->whereUuid('uuid');

// ── JSON Endpoints for React Query (Internal Web API) ──
// These endpoints are used by the frontend React components via React Query
Route::prefix('data')->group(function () {
    // Current user profile
    Route::get('/me', [StudentController::class, 'show'])->name('student.data.me');
    Route::put('/me', [StudentController::class, 'update'])->name('student.data.me.update');

    // Admin
    Route::prefix('admin')->group(function () {
        Route::get('/export', [StudentExportController::class, '__invoke'])->name('student.data.export'); // MUST be before /{uuid}
        Route::get('/', [StudentController::class, 'index'])->name('student.data.index');
        Route::post('/', [StudentController::class, 'store'])->name('student.data.store');
        Route::post('/bulk-delete', [StudentController::class, 'bulkDelete'])->name('student.data.bulk-delete');
        Route::get('/{uuid}', [StudentController::class, 'show'])->name('student.data.show')->whereUuid('uuid');
        Route::put('/{uuid}', [StudentController::class, 'update'])->name('student.data.update')->whereUuid('uuid');
        Route::delete('/{uuid}', [StudentController::class, 'destroy'])->name('student.data.destroy')->whereUuid('uuid');
        Route::patch('/{uuid}/restore', [StudentController::class, 'restore'])->name('student.data.restore')->whereUuid('uuid');
    });
});
