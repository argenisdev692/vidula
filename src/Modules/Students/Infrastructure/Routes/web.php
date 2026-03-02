<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Modules\Student\Infrastructure\Http\Controllers\Api\StudentController;

/**
 * Student Context — Web routes (Inertia pages + JSON endpoints for React Query).
 *
 * Prefix: /student (applied by ServiceProvider).
 * Middleware: web, auth (applied by ServiceProvider).
 */

// ── Inertia Pages ──
Route::get('/', function () {
    return Inertia::render('student/StudentIndexPage');
})->name('student.index');

Route::get('/create', function () {
    return Inertia::render('student/StudentCreatePage');
})->name('student.create');

Route::get('/{uuid}', function (string $uuid) {
    return Inertia::render('student/StudentShowPage', ['companyId' => $uuid]);
})->name('student.show')->whereUuid('uuid');

Route::get('/{uuid}/edit', function (string $uuid) {
    return Inertia::render('student/StudentEditPage', ['companyId' => $uuid]);
})->name('student.edit')->whereUuid('uuid');

// ── JSON Endpoints for React Query (Internal Web API) ──
// These endpoints are used by the frontend React components via React Query
Route::prefix('data')->group(function () {
    // Current user profile
    Route::get('/me', [StudentController::class, 'show'])->name('student.data.me');
    Route::put('/me', [StudentController::class, 'update'])->name('student.data.me.update');

    // Admin
    Route::prefix('admin')->group(function () {
        Route::get('/export', [StudentController::class, 'export'])->name('student.data.export');
        Route::get('/', [StudentController::class, 'index'])->name('student.data.index');
        Route::post('/', [StudentController::class, 'store'])->name('student.data.store');
        Route::get('/{uuid}', [StudentController::class, 'show'])->name('student.data.show')->whereUuid('uuid');
        Route::put('/{uuid}', [StudentController::class, 'update'])->name('student.data.update')->whereUuid('uuid');
        Route::delete('/{uuid}', [StudentController::class, 'destroy'])->name('student.data.destroy')->whereUuid('uuid');
        Route::patch('/{uuid}/restore', [StudentController::class, 'restore'])->name('student.data.restore')->whereUuid('uuid');
        Route::post('/bulk-delete', [StudentController::class, 'bulkDelete'])->name('student.data.bulk-delete');
    });
});
