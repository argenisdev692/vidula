<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Company\Infrastructure\Http\Controllers\CompanyBrandingController;

/*
| Company module — web (session + Inertia).
|
| Branding management is COMPANY_DATA-gated (UI authz uses permissions, never
| roles). No dedicated admin page yet — the endpoint backs the auto-managed
| branding used by emails, the login hero and the navbar.
*/

Route::middleware(['web', 'auth'])->prefix('settings/company')->name('settings.company.')->group(function (): void {
    Route::post('/logo', [CompanyBrandingController::class, 'update'])
        ->middleware('permission:UPDATE_COMPANY_DATA')->name('logo.update');
});
