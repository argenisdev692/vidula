<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Company\Infrastructure\Http\Controllers\Api\PublicCompanyDataController;

/*
| Company API routes.
|
| `/company-data/public` is the CRM-token-gated landing-page singleton (Astro).
| Admin CRUD stays on the web/Inertia surface — no Sanctum mirror needed yet.
*/
Route::get('/company-data/public', [PublicCompanyDataController::class, 'show'])
    ->middleware(['crm.token', 'throttle:60,1'])
    ->name('api.company-data.public');
