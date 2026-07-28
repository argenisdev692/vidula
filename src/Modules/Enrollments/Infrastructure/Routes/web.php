<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Enrollments\Infrastructure\Http\Controllers\AttendanceExportController;
use Modules\Enrollments\Infrastructure\Http\Controllers\EnrollmentController;

/*
| Enrollments module — web (session + Inertia).
| Static segments BEFORE `{uuid}` so bulk/attendance never capture as UUIDs.
*/
Route::middleware(['web', 'auth', 'throttle:60,1'])->prefix('enrollments')->name('enrollments.')->group(function (): void {
    Route::get('/', [EnrollmentController::class, 'index'])
        ->middleware('permission:VIEW_ANY_ENROLLMENTS')->name('index');

    Route::post('/', [EnrollmentController::class, 'store'])
        ->middleware('permission:CREATE_ENROLLMENTS')->name('store');

    Route::post('/bulk-delete', [EnrollmentController::class, 'bulkDelete'])
        ->middleware('permission:BULK_DELETE_ENROLLMENTS')->name('bulk-delete');

    Route::post('/bulk-restore', [EnrollmentController::class, 'bulkRestore'])
        ->middleware('permission:BULK_RESTORE_ENROLLMENTS')->name('bulk-restore');

    Route::get('/attendance/{classroomUuid}', [EnrollmentController::class, 'attendanceSheet'])
        ->middleware('permission:VIEW_ANY_ENROLLMENTS')
        ->whereUuid('classroomUuid')
        ->name('attendance');

    Route::put('/attendance/{classroomUuid}', [EnrollmentController::class, 'syncAttendance'])
        ->middleware('permission:UPDATE_ENROLLMENTS')
        ->whereUuid('classroomUuid')
        ->name('attendance.sync');

    Route::get('/attendance/{classroomUuid}/export', AttendanceExportController::class)
        ->middleware(['permission:EXPORT_ENROLLMENTS', 'throttle:10,1'])
        ->whereUuid('classroomUuid')
        ->name('attendance.export');

    Route::get('/{uuid}', [EnrollmentController::class, 'show'])
        ->middleware('permission:VIEW_ENROLLMENTS')->whereUuid('uuid')->name('show');

    Route::put('/{uuid}', [EnrollmentController::class, 'update'])
        ->middleware('permission:UPDATE_ENROLLMENTS')->whereUuid('uuid')->name('update');

    Route::delete('/{uuid}', [EnrollmentController::class, 'destroy'])
        ->middleware('permission:DELETE_ENROLLMENTS')->whereUuid('uuid')->name('destroy');

    Route::post('/{uuid}/restore', [EnrollmentController::class, 'restore'])
        ->middleware('permission:RESTORE_ENROLLMENTS')->whereUuid('uuid')->name('restore');
});
