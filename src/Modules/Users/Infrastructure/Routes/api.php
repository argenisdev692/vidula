<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Users\Infrastructure\Http\Controllers\UserAvailabilityController;

/*
| Users module — API (Sanctum token, mobile/external). Loaded with the `api`
| middleware group by UsersServiceProvider. Never call these from the Inertia
| web app (that uses the session routes in web.php).
*/

Route::prefix('api/users')->middleware(['auth:sanctum', 'throttle:60,1'])->name('api.users.')->group(function (): void {
    // Realtime username/email availability for the create/edit forms.
    Route::get('/availability', UserAvailabilityController::class)
        ->middleware('permission:CREATE_USERS|UPDATE_USERS')->name('availability');
});
