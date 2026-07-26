<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\VideoExport\Infrastructure\Http\Controllers\VideoExportController;

Route::middleware(['web', 'auth'])->prefix('video-export')->name('video-export.')->group(function (): void {
    Route::get('/', [VideoExportController::class, 'index'])
        ->middleware('permission:VIEW_ANY_VIDEO_EXPORTS')
        ->name('index');

    Route::post('/uploads/presign', [VideoExportController::class, 'presign'])
        ->middleware(['permission:CREATE_VIDEO_EXPORTS', 'throttle:60,1'])
        ->name('presign');

    Route::post('/', [VideoExportController::class, 'store'])
        ->middleware(['permission:CREATE_VIDEO_EXPORTS', 'throttle:20,1'])
        ->name('store');

    Route::get('/jobs/{job_uuid}', [VideoExportController::class, 'jobStatus'])
        ->middleware('permission:VIEW_ANY_VIDEO_EXPORTS')
        ->whereUuid('job_uuid')
        ->name('jobs.show');
});
