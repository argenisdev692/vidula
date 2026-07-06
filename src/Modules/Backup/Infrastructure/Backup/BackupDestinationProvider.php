<?php

declare(strict_types=1);

namespace Modules\Backup\Infrastructure\Backup;

use Spatie\Backup\BackupDestination\BackupDestination;

/**
 * Resolves the configured spatie/laravel-backup destination (disk + backup name)
 * from `config/backup.php`. Single source so the list, download and delete flows
 * all read from the exact same place the scheduled `backup:*` commands write to.
 */
final readonly class BackupDestinationProvider
{
    public function destination(): BackupDestination
    {
        return BackupDestination::create($this->diskName(), $this->backupName());
    }

    public function diskName(): string
    {
        /** @var array<int, string> $disks */
        $disks = (array) config('backup.backup.destination.disks', ['local']);

        return (string) ($disks[0] ?? 'local');
    }

    public function backupName(): string
    {
        return (string) config('backup.backup.name', config('app.name', 'laravel-backup'));
    }
}
