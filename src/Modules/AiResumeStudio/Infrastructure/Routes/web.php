<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\AiResumeStudio\Infrastructure\Http\Controllers\JobMatchExportController;
use Modules\AiResumeStudio\Infrastructure\Http\Controllers\RefinedCvExportController;
use Modules\AiResumeStudio\Infrastructure\Http\Controllers\ResumeStudioController;

/*
| AiResumeStudio — web (session + Inertia).
| Static segments BEFORE `{uuid}` so bulk/export are never captured as UUIDs.
*/
Route::middleware(['web', 'auth', 'throttle:60,1'])->prefix('resume-studio')->name('resume-studio.')->group(function (): void {
    Route::get('/', [ResumeStudioController::class, 'index'])
        ->middleware('permission:VIEW_ANY_RESUME_STUDIOS')->name('index');

    Route::post('/runs', [ResumeStudioController::class, 'startRun'])
        ->middleware(['permission:RUN_RESUME_STUDIOS', 'throttle:10,1'])->name('runs.store');

    Route::post('/configs', [ResumeStudioController::class, 'storeConfig'])
        ->middleware('permission:CREATE_RESUME_STUDIOS')->name('configs.store');

    Route::post('/github/repos', [ResumeStudioController::class, 'listGithubRepos'])
        ->middleware(['permission:RUN_RESUME_STUDIOS', 'throttle:10,1'])->name('github.repos');

    Route::post('/matches/bulk-delete', [ResumeStudioController::class, 'bulkDeleteMatches'])
        ->middleware('permission:BULK_DELETE_RESUME_STUDIOS')->name('matches.bulk-delete');

    Route::post('/matches/bulk-restore', [ResumeStudioController::class, 'bulkRestoreMatches'])
        ->middleware('permission:BULK_RESTORE_RESUME_STUDIOS')->name('matches.bulk-restore');

    Route::get('/export', JobMatchExportController::class)
        ->middleware(['permission:EXPORT_RESUME_STUDIOS', 'throttle:10,1'])->name('export');

    Route::get('/refined/{uuid}/pdf', RefinedCvExportController::class)
        ->middleware(['permission:EXPORT_RESUME_STUDIOS', 'throttle:10,1'])
        ->whereUuid('uuid')
        ->name('refined.pdf');

    Route::get('/runs/{uuid}', [ResumeStudioController::class, 'show'])
        ->middleware('permission:VIEW_RESUME_STUDIOS')->whereUuid('uuid')->name('runs.show');

    Route::post('/runs/{uuid}/metrics', [ResumeStudioController::class, 'submitMetrics'])
        ->middleware(['permission:RUN_RESUME_STUDIOS', 'throttle:10,1'])
        ->whereUuid('uuid')
        ->name('runs.metrics');

    Route::patch('/matches/{uuid}', [ResumeStudioController::class, 'updateMatch'])
        ->middleware('permission:UPDATE_RESUME_STUDIOS')->whereUuid('uuid')->name('matches.update');

    Route::post('/drafts/{uuid}/mark-sent', [ResumeStudioController::class, 'markDraftSent'])
        ->middleware('permission:UPDATE_RESUME_STUDIOS')->whereUuid('uuid')->name('drafts.mark-sent');
});
