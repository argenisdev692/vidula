<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Company\Infrastructure\Http\Controllers\CompanyDataController;

/*
| Company module — web (session + Inertia).
|
| Settings singleton: the record is seeded, never created or deleted through the
| UI. Data routes are COMPANY_DATA-gated (UI authz uses permissions, never
| roles). Asset routes keep the legacy `settings/company` prefix so the branding
| that backs emails, the login hero and the navbar stays wired.
*/

// ---- Admin: company settings data -------------------------------------------
Route::middleware(['web', 'auth'])->prefix('company-data')->name('company-data.')->group(function (): void {
    Route::get('/', [CompanyDataController::class, 'index'])
        ->middleware('permission:VIEW_ANY_COMPANY_DATA')->name('index');

    Route::get('/{uuid}', [CompanyDataController::class, 'show'])
        ->middleware('permission:VIEW_COMPANY_DATA')->whereUuid('uuid')->name('show');

    Route::put('/{uuid}', [CompanyDataController::class, 'update'])
        ->middleware('permission:UPDATE_COMPANY_DATA')->whereUuid('uuid')->name('update');
});

// ---- Admin: brand + signature assets ----------------------------------------
Route::middleware(['web', 'auth'])->prefix('settings/company')->name('settings.company.')->group(function (): void {
    Route::post('/logo', [CompanyDataController::class, 'updateLogo'])
        ->middleware('permission:UPDATE_COMPANY_DATA')->name('logo.update');

    Route::delete('/logo/{type}', [CompanyDataController::class, 'destroyLogo'])
        ->middleware('permission:UPDATE_COMPANY_DATA')->where('type', 'logo|logo_white|mark')->name('logo.destroy');

    Route::post('/signature', [CompanyDataController::class, 'updateSignature'])
        ->middleware('permission:UPDATE_COMPANY_DATA')->name('signature.update');

    Route::delete('/signature', [CompanyDataController::class, 'destroySignature'])
        ->middleware('permission:UPDATE_COMPANY_DATA')->name('signature.destroy');
});
