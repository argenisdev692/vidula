<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Blog\Infrastructure\Http\Controllers\Api\BlogCategoryApiController;

/*
| Blog category API routes. Secondary surface for Sanctum-authenticated
| clients. Documented by Scramble under /api/blog-categories.
*/
Route::middleware('auth:sanctum')
    ->prefix('blog-categories')
    ->name('api.blog-categories.')
    ->group(function (): void {
        Route::get('/', [BlogCategoryApiController::class, 'index'])->name('index');
        Route::get('/{uuid}', [BlogCategoryApiController::class, 'show'])
            ->whereUuid('uuid')
            ->name('show');
    });
