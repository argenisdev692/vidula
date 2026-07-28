<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Clients\Infrastructure\Http\Controllers\Api\ClientApiController;

/*
| Clients API — Sanctum secondary surface (read lookup for Scramble / mobile).
| Writes stay on the Inertia web controller.
*/
Route::middleware(['auth:sanctum', 'throttle:60,1'])
    ->prefix('clients')
    ->name('api.clients.')
    ->group(function (): void {
        Route::get('/', [ClientApiController::class, 'index'])
            ->middleware('permission:VIEW_ANY_CLIENTS')
            ->name('index');
        Route::get('/{uuid}', [ClientApiController::class, 'show'])
            ->middleware('permission:VIEW_CLIENTS')
            ->whereUuid('uuid')
            ->name('show');
    });
