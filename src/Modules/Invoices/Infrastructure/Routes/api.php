<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Invoices\Infrastructure\Http\Controllers\Api\InvoiceApiController;

/*
| Invoices API — Sanctum secondary surface (read lookup for Scramble / mobile).
| Writes stay on the Inertia web controller.
*/
Route::middleware(['auth:sanctum', 'throttle:60,1'])
    ->prefix('invoices')
    ->name('api.invoices.')
    ->group(function (): void {
        Route::get('/', [InvoiceApiController::class, 'index'])->name('index');
        Route::get('/{uuid}', [InvoiceApiController::class, 'show'])
            ->whereUuid('uuid')
            ->name('show');
    });
