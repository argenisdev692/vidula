<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Students\Infrastructure\Http\Controllers\Api\StudentApiController;

/*
| Students API — Sanctum secondary surface (read lookup for Scramble / mobile).
*/
Route::middleware(['auth:sanctum', 'throttle:60,1'])
    ->prefix('students')
    ->name('api.students.')
    ->group(function (): void {
        Route::get('/', [StudentApiController::class, 'index'])->name('index');
        Route::get('/{uuid}', [StudentApiController::class, 'show'])
            ->whereUuid('uuid')
            ->name('show');
    });
