<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\ContactSupport\Infrastructure\Http\Controllers\Api\ContactSupportApiController;

/*
| Contact-support API routes. Secondary surface for Sanctum-authenticated
| clients. Documented by Scramble under /api/contact-supports.
*/
Route::middleware('auth:sanctum')
    ->prefix('contact-supports')
    ->name('api.contact-supports.')
    ->group(function (): void {
        Route::get('/', [ContactSupportApiController::class, 'index'])->name('index');
        Route::get('/{uuid}', [ContactSupportApiController::class, 'show'])
            ->whereUuid('uuid')
            ->name('show');
    });
