<?php

declare(strict_types=1);

namespace Modules\Backup\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Backup\Application\Commands\DeleteBackupHandler;
use Modules\Backup\Application\Commands\RunBackupHandler;
use Modules\Backup\Application\Queries\FindBackupHandler;
use Modules\Backup\Application\Queries\ListBackupsHandler;
use Modules\Backup\Infrastructure\Http\Presenters\BackupPresenter;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin backups panel over spatie/laravel-backup. Every route is authorized via
 * `permission:*_BACKUPS` middleware (not roles). Backups are files on a disk, not
 * DB rows — the controller stays thin: resolve via handler → response.
 */
final readonly class BackupController
{
    public function index(ListBackupsHandler $list): InertiaResponse|JsonResponse
    {
        $data = $list->handle();

        return request()->expectsJson()
            ? response()->json($data)
            : Inertia::render('backups/Index', $data);
    }

    public function download(string $backup, FindBackupHandler $find): StreamedResponse
    {
        $found = $find->handle($backup);

        return $found->disk()->download($found->path(), BackupPresenter::downloadName($found));
    }

    public function store(Request $request, RunBackupHandler $run): RedirectResponse
    {
        $run->handle($request->user());

        return back()->with('success', __('Backup queued — it will appear here once the job finishes.'));
    }

    public function destroy(string $backup, Request $request, DeleteBackupHandler $delete): RedirectResponse
    {
        $delete->handle($backup, $request->user());

        return back()->with('success', __('Backup deleted.'));
    }
}
