<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\VideoExport\Infrastructure\Http\Controllers\Api\VideoExportApiController;

/*
| Video Export — API (Sanctum). Secondary surface for mobile clients; web/Inertia
| remains primary. Documented by Scramble under /api/video-export.
*/

Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('video-export')->name('api.video-export.')->group(function (): void {
    Route::post('/uploads/presign', [VideoExportApiController::class, 'presign'])
        ->middleware('throttle:60,1')
        ->name('presign');

    Route::post('/', [VideoExportApiController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('store');

    Route::get('/jobs/{job_uuid}', [VideoExportApiController::class, 'jobStatus'])
        ->whereUuid('job_uuid')
        ->name('jobs.show');
});
