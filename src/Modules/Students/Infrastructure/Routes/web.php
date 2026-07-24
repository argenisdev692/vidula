<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Students\Infrastructure\Http\Controllers\StudentController;

/*
| Students module — web (session + Inertia).
|
| Static segments BEFORE `{uuid}` so bulk actions are never captured as UUIDs.
*/
Route::middleware(['web', 'auth', 'throttle:60,1'])->prefix('students')->name('students.')->group(function (): void {
    Route::get('/', [StudentController::class, 'index'])
        ->middleware('permission:VIEW_ANY_STUDENTS')->name('index');

    Route::post('/', [StudentController::class, 'store'])
        ->middleware('permission:CREATE_STUDENTS')->name('store');

    Route::post('/bulk-delete', [StudentController::class, 'bulkDelete'])
        ->middleware('permission:BULK_DELETE_STUDENTS')->name('bulk-delete');

    Route::post('/bulk-restore', [StudentController::class, 'bulkRestore'])
        ->middleware('permission:BULK_RESTORE_STUDENTS')->name('bulk-restore');

    Route::get('/{uuid}', [StudentController::class, 'show'])
        ->middleware('permission:VIEW_STUDENTS')->whereUuid('uuid')->name('show');

    Route::put('/{uuid}', [StudentController::class, 'update'])
        ->middleware('permission:UPDATE_STUDENTS')->whereUuid('uuid')->name('update');

    Route::delete('/{uuid}', [StudentController::class, 'destroy'])
        ->middleware('permission:DELETE_STUDENTS')->whereUuid('uuid')->name('destroy');

    Route::post('/{uuid}/restore', [StudentController::class, 'restore'])
        ->middleware('permission:RESTORE_STUDENTS')->whereUuid('uuid')->name('restore');
});
