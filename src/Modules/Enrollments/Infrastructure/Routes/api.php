<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Enrollments\Infrastructure\Http\Controllers\Api\EnrollmentApiController;

/*
| Enrollments API — Sanctum secondary surface (read lookup for Scramble / mobile).
*/
Route::middleware(['auth:sanctum', 'throttle:60,1'])
    ->prefix('enrollments')
    ->name('api.enrollments.')
    ->group(function (): void {
        Route::get('/', [EnrollmentApiController::class, 'index'])->name('index');

        Route::get('/attendance/{classroomUuid}', [EnrollmentApiController::class, 'attendanceSheet'])
            ->whereUuid('classroomUuid')
            ->name('attendance');

        Route::get('/{uuid}', [EnrollmentApiController::class, 'show'])
            ->whereUuid('uuid')
            ->name('show');
    });
