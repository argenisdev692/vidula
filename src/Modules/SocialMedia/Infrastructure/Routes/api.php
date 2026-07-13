<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\SocialMedia\Infrastructure\Http\Controllers\Api\SocialMediaApiController;

/*
| Social Media API routes.
|
| Secondary Sanctum-authenticated surface (mobile/external clients) mirroring
| Post's `api.php`. `/social-media/{uuid}` is registered after the AI static
| segments so "ai" is never captured as a UUID (whereUuid rejects it anyway,
| but static-before-wildcard is the project convention regardless).
*/
Route::middleware(['auth:sanctum', 'throttle:60,1'])
    ->prefix('social-media')
    ->name('api.social-media.')
    ->group(function (): void {
        Route::get('/', [SocialMediaApiController::class, 'index'])->name('index');

        Route::post('/ai/suggest-topics', [SocialMediaApiController::class, 'suggestTopics'])
            ->middleware('throttle:10,1')->name('ai.suggest-topics');
        Route::post('/ai/generate-content', [SocialMediaApiController::class, 'generateContent'])
            ->middleware('throttle:5,1')->name('ai.generate-content');

        Route::get('/{uuid}', [SocialMediaApiController::class, 'show'])
            ->whereUuid('uuid')
            ->name('show');
    });
