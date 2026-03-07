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
Route::get('/', [StudentPageController::class, 'index'])->name('student.index')->middleware('permission:VIEW_STUDENTS');
Route::get('/create', [StudentPageController::class, 'create'])->name('student.create')->middleware('permission:CREATE_STUDENTS');
Route::get('/{uuid}', [StudentPageController::class, 'show'])->name('student.show')->whereUuid('uuid')->middleware('permission:VIEW_STUDENTS');
Route::get('/{uuid}/edit', [StudentPageController::class, 'edit'])->name('student.edit')->whereUuid('uuid')->middleware('permission:UPDATE_STUDENTS');

// ── JSON Endpoints for React Query (Internal Web API) ──
// These endpoints are used by the frontend React components via React Query
Route::prefix('data')->group(function () {
    // Admin
    Route::prefix('admin')->group(function () {
        Route::get('/export', [StudentExportController::class, '__invoke'])->name('student.data.export')->middleware('permission:VIEW_STUDENTS');
        Route::get('/', [StudentController::class, 'index'])->name('student.data.index')->middleware('permission:VIEW_STUDENTS');
        Route::post('/', [StudentController::class, 'store'])->name('student.data.store')->middleware('permission:CREATE_STUDENTS');
        Route::post('/bulk-delete', [StudentController::class, 'bulkDelete'])->name('student.data.bulk-delete')->middleware('permission:DELETE_STUDENTS');
        Route::get('/{uuid}', [StudentController::class, 'show'])->name('student.data.show')->whereUuid('uuid')->middleware('permission:VIEW_STUDENTS');
        Route::put('/{uuid}', [StudentController::class, 'update'])->name('student.data.update')->whereUuid('uuid')->middleware('permission:UPDATE_STUDENTS');
        Route::delete('/{uuid}', [StudentController::class, 'destroy'])->name('student.data.destroy')->whereUuid('uuid')->middleware('permission:DELETE_STUDENTS');
        Route::patch('/{uuid}/restore', [StudentController::class, 'restore'])->name('student.data.restore')->whereUuid('uuid')->middleware('permission:DELETE_STUDENTS');
    });
});
