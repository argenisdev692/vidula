<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Backup\Infrastructure\Http\Controllers\Api\BackupApiController;

/*
| Backup module — API (Sanctum). Secondary surface for mobile clients; the
| web/Inertia routes remain primary. Documented by Scramble (prefix api/*). The
| `{backup}` param is a file basename, charset-constrained and re-resolved
| against the live archive list.
*/

Route::middleware('auth:sanctum')->prefix('backups')->name('api.backups.')->group(function (): void {
    Route::get('/', [BackupApiController::class, 'index'])->name('index');
    Route::post('/run', [BackupApiController::class, 'run'])->name('run');
    Route::get('/{backup}/download', [BackupApiController::class, 'download'])
        ->where('backup', '[A-Za-z0-9._\- ]+')->name('download');
    Route::delete('/{backup}', [BackupApiController::class, 'destroy'])
        ->where('backup', '[A-Za-z0-9._\- ]+')->name('destroy');
});
