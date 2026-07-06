<?php

declare(strict_types=1);

namespace Modules\Backup\Application\Queries;

use Modules\Backup\Infrastructure\Backup\BackupDestinationProvider;
use Modules\Backup\Infrastructure\Http\Presenters\BackupPresenter;
use Spatie\Backup\BackupDestination\Backup;

/**
 * Reads the configured backup destination and returns the list of archives plus
 * a health/status summary (reachability, freshness, count, storage used) — the
 * same signals the scheduled `backup:monitor` command evaluates.
 */
final readonly class ListBackupsHandler
{
    public function __construct(private BackupDestinationProvider $provider) {}

    /**
     * @return array{backups: array<int, array<string, mixed>>, status: array<string, mixed>}
     */
    public function handle(): array
    {
        $destination = $this->provider->destination();
        $diskName = $this->provider->diskName();
        $backups = $destination->backups();
        $newest = $destination->newestBackup();
        $reachable = $destination->isReachable();
        $usedBytes = (int) $destination->usedStorage();

        $items = $backups
            ->map(fn (Backup $backup): array => BackupPresenter::toItem($backup, $diskName))
            ->values()
            ->all();

        return [
            'backups' => $items,
            'status' => [
                'reachable' => $reachable,
                'healthy' => $reachable && $newest !== null && ! $destination->newestBackupIsOlderThan(now()->subDay()),
                'backup_count' => $backups->count(),
                'newest_at' => $newest?->date()->toIso8601String(),
                'used_storage_bytes' => $usedBytes,
                'used_storage_human' => BackupPresenter::humanSize($usedBytes),
                'disk' => $diskName,
                'backup_name' => $this->provider->backupName(),
            ],
        ];
    }
}
