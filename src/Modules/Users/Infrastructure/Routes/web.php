<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Users\Infrastructure\Http\Controllers\AccountActivationController;
use Modules\Users\Infrastructure\Http\Controllers\UserController;
use Modules\Users\Infrastructure\Http\Controllers\UserExportController;

/*
| Users module — web (session + Inertia).
|
| Management routes are SUPER_ADMIN-only in practice: gated by `permission:*_USERS`
| (UI authz uses permissions, never roles). The activation route is the only
| public one — protected by a signed link + throttle, not auth.
*/

// ---- Public: invitation activation (set password + verify + auto-login) -----
Route::match(['get', 'post'], '/users/activate/{uuid}', AccountActivationController::class)
    ->middleware(['web', 'signed', 'throttle:6,1'])
    ->whereUuid('uuid')
    ->name('users.activation.show');

// ---- Admin management -------------------------------------------------------
Route::middleware(['web', 'auth'])->prefix('users')->name('users.')->group(function (): void {
    // Static segments BEFORE the wildcard.
    Route::get('/', [UserController::class, 'index'])
        ->middleware('permission:VIEW_ANY_USERS')->name('index');

    Route::get('/export', UserExportController::class)
        ->middleware('permission:VIEW_ANY_USERS')->name('export');

    Route::post('/', [UserController::class, 'store'])
        ->middleware('permission:CREATE_USERS')->name('store');

    Route::post('/bulk-delete', [UserController::class, 'bulkDelete'])
        ->middleware('permission:BULK_DELETE_USERS')->name('bulk-delete');

    Route::post('/bulk-restore', [UserController::class, 'bulkRestore'])
        ->middleware('permission:BULK_RESTORE_USERS')->name('bulk-restore');

    Route::get('/{uuid}', [UserController::class, 'show'])
        ->middleware('permission:VIEW_USERS')->whereUuid('uuid')->name('show');

    Route::put('/{uuid}', [UserController::class, 'update'])
        ->middleware('permission:UPDATE_USERS')->whereUuid('uuid')->name('update');

    Route::delete('/{uuid}', [UserController::class, 'destroy'])
        ->middleware('permission:DELETE_USERS')->whereUuid('uuid')->name('destroy');

    Route::post('/{uuid}/restore', [UserController::class, 'restore'])
        ->middleware('permission:RESTORE_USERS')->whereUuid('uuid')->name('restore');

    Route::post('/{uuid}/resend-invitation', [UserController::class, 'resendInvitation'])
        ->middleware('permission:CREATE_USERS')->whereUuid('uuid')->name('resend-invitation');
});
