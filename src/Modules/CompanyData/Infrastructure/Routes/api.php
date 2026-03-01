<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\CompanyData\Infrastructure\Http\Controllers\Api\CompanyDataController;

/**
 * CompanyData Context — API routes.
 */
Route::prefix('admin')->group(function () {
    Route::get('/', [CompanyDataController::class, 'index'])->name('api.admin.company_data.index');
    Route::post('/', [CompanyDataController::class, 'store'])->name('api.admin.company_data.store');
    Route::get('/{uuid}', [CompanyDataController::class, 'show'])->name('api.admin.company_data.show')->whereUuid('uuid');
    Route::put('/{uuid}', [CompanyDataController::class, 'update'])->name('api.admin.company_data.update')->whereUuid('uuid');
    Route::delete('/{uuid}', [CompanyDataController::class, 'destroy'])->name('api.admin.company_data.destroy')->whereUuid('uuid');
    Route::patch('/{uuid}/restore', [CompanyDataController::class, 'restore'])->name('api.admin.company_data.restore')->whereUuid('uuid');
});

// For current user profile data
Route::get('/me', [CompanyDataController::class, 'show'])->name('api.company_data.me');
Route::put('/me', [CompanyDataController::class, 'update'])->name('api.company_data.me.update');
