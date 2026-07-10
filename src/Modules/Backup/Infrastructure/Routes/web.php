<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Backup\Infrastructure\Http\Controllers\BackupController;

/*
| Backup module — web (session + Inertia).
|
| Admin-only backups panel over spatie/laravel-backup. UI authorization uses
| permissions (`*_BACKUPS`), never roles. The `{backup}` param is a file basename
| constrained to a safe charset AND re-resolved against the live archive list, so
| no caller-supplied path ever reaches the filesystem.
*/

Route::middleware(['web', 'auth', 'throttle:60,1'])->prefix('backups')->name('backups.')->group(function (): void {
    Route::get('/', [BackupController::class, 'index'])
        ->middleware('permission:VIEW_ANY_BACKUPS')->name('index');

    Route::post('/run', [BackupController::class, 'store'])
        ->middleware(['permission:CREATE_BACKUPS', 'throttle:6,1'])->name('run');

    Route::get('/{backup}/download', [BackupController::class, 'download'])
        ->middleware('permission:DOWNLOAD_BACKUPS')->where('backup', '[A-Za-z0-9._\- ]+')->name('download');

    Route::delete('/{backup}', [BackupController::class, 'destroy'])
        ->middleware('permission:DELETE_BACKUPS')->where('backup', '[A-Za-z0-9._\- ]+')->name('destroy');
});
