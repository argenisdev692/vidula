<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Post\Infrastructure\Http\Controllers\Api\PostApiController;
use Modules\Post\Infrastructure\Http\Controllers\Api\PublicPostController;

/*
| Post API routes.
|
| `/posts/public` (list, ?category_uuid=) and `/posts/public/{slug}` (detail)
| are the unauthenticated landing-page feed (only `published` posts) —
| registered BEFORE the sanctum group's `{uuid}` so they are never captured as
| a UUID. `/posts` (sanctum) is the secondary authenticated lookup surface
| mirroring the web CRUD.
*/
Route::middleware('throttle:60,1')->group(function (): void {
    Route::get('/posts/public', [PublicPostController::class, 'index'])->name('api.posts.public');
    Route::get('/posts/public/{slug}', [PublicPostController::class, 'show'])->name('api.posts.public.show');
});

Route::middleware(['auth:sanctum', 'throttle:60,1'])
    ->prefix('posts')
    ->name('api.posts.')
    ->group(function (): void {
        Route::get('/', [PostApiController::class, 'index'])->name('index');
        Route::get('/{uuid}', [PostApiController::class, 'show'])
            ->whereUuid('uuid')
            ->name('show');
    });
