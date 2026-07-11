<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Services\Infrastructure\Http\Controllers\Api\PublicServiceController;

/*
| Services API routes.
|
| `/services/public` is the unauthenticated landing-page select-input feed
| (only `is_active` records). No secondary Sanctum-authenticated surface is
| shipped here (YAGNI) — the web CRUD's JSON branch already serves
| authenticated lookups.
*/
Route::get('/services/public', [PublicServiceController::class, 'index'])
    ->middleware('throttle:60,1')
    ->name('api.services.public');
