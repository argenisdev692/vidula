<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Clients\Infrastructure\Http\Controllers\Api\ClientApiController;

/*
| Clients API — Sanctum secondary surface (read lookup for Scramble / mobile).
*/
Route::middleware(['auth:sanctum', 'throttle:60,1'])
    ->prefix('clients')
    ->name('api.clients.')
    ->group(function (): void {
        Route::get('/', [ClientApiController::class, 'index'])->name('index');
        Route::get('/{uuid}', [ClientApiController::class, 'show'])
            ->whereUuid('uuid')
            ->name('show');
    });
