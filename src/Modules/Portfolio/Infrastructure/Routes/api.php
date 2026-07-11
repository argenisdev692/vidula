<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Portfolio\Infrastructure\Http\Controllers\Api\PortfolioApiController;
use Modules\Portfolio\Infrastructure\Http\Controllers\Api\PublicPortfolioController;

/*
| Portfolio API routes.
|
| `/portfolios/public` is the unauthenticated landing-page feed (only
| `is_public` records) — registered BEFORE the sanctum group's `{uuid}` so it
| is never captured as a UUID. `/portfolios` (sanctum) is the secondary
| authenticated lookup surface mirroring the web CRUD.
*/
Route::get('/portfolios/public', [PublicPortfolioController::class, 'index'])
    ->middleware('throttle:60,1')
    ->name('api.portfolios.public');

Route::middleware(['auth:sanctum', 'throttle:60,1'])
    ->prefix('portfolios')
    ->name('api.portfolios.')
    ->group(function (): void {
        Route::get('/', [PortfolioApiController::class, 'index'])->name('index');
        Route::get('/{uuid}', [PortfolioApiController::class, 'show'])->whereUuid('uuid')->name('show');
    });
