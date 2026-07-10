<?php

declare(strict_types=1);

namespace Modules\Backup\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Backup\Application\Commands\DeleteBackupHandler;
use Modules\Backup\Application\Commands\RunBackupHandler;
use Modules\Backup\Application\Queries\FindBackupHandler;
use Modules\Backup\Application\Queries\ListBackupsHandler;
use Modules\Backup\Infrastructure\Http\Presenters\BackupPresenter;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @group Backups
 *
 * Admin backups panel over spatie/laravel-backup for Sanctum-authenticated
 * clients. Reuses the same handlers as the web controller. Authorization is
 * checked on the model (`hasPermissionTo`) so it is safe under the `sanctum`
 * guard. The `{backup}` segment is a file basename, re-resolved against the live
 * archive list (no caller-supplied path reaches the filesystem).
 */
final readonly class BackupApiController
{
    /**
     * List backups
     *
     * Returns every archive plus a health/status summary (reachability,
     * freshness, count, storage used).
     *
     * @authenticated
     *
     * @response 403 {"message": "This action is unauthorized."}
     */
    public function index(Request $request, ListBackupsHandler $list): JsonResponse
    {
        abort_unless((bool) $request->user()?->hasPermissionTo('VIEW_ANY_BACKUPS'), 403);

        return response()->json($list->handle());
    }

    /**
     * Download a backup
     *
     * Streams the backup ZIP as a file download.
     *
     * @authenticated
     *
     * @urlParam backup string required The backup file basename. Example: 2026-07-06-02-00-00.zip
     *
     * @response 404 {"message": "Backup not found."}
     */
    public function download(Request $request, string $backup, FindBackupHandler $find): StreamedResponse
    {
        abort_unless((bool) $request->user()?->hasPermissionTo('DOWNLOAD_BACKUPS'), 403);

        $found = $find->handle($backup);

        return $found->disk()->download($found->path(), BackupPresenter::downloadName($found));
    }

    /**
     * Run a backup now
     *
     * Queues a full (database + files) backup on a background worker.
     *
     * @authenticated
     *
     * @response 202 {"message": "Backup queued."}
     */
    public function run(Request $request, RunBackupHandler $run): JsonResponse
    {
        abort_unless((bool) $request->user()?->hasPermissionTo('CREATE_BACKUPS'), 403);

        $run->handle($request->user());

        return response()->json(['message' => __('Backup queued.')], 202);
    }

    /**
     * Delete a backup
     *
     * Permanently removes a single archive from the destination disk.
     *
     * @authenticated
     *
     * @urlParam backup string required The backup file basename. Example: 2026-07-06-02-00-00.zip
     *
     * @response 200 {"message": "Backup deleted."}
     */
    public function destroy(Request $request, string $backup, DeleteBackupHandler $delete): JsonResponse
    {
        abort_unless((bool) $request->user()?->hasPermissionTo('DELETE_BACKUPS'), 403);

        $delete->handle($backup, $request->user());

        return response()->json(['message' => __('Backup deleted.')]);
    }
}
