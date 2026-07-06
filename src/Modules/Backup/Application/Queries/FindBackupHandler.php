<?php

declare(strict_types=1);

namespace Modules\Backup\Application\Queries;

use Modules\Backup\Infrastructure\Backup\BackupDestinationProvider;
use Spatie\Backup\BackupDestination\Backup;

/**
 * Resolves a single {@see Backup} by its file basename against the LIVE list of
 * archives. Because the match is done on the real collection (never by composing
 * a path from the request), path traversal is impossible — an unknown id 404s.
 */
final readonly class FindBackupHandler
{
    public function __construct(private BackupDestinationProvider $provider) {}

    public function handle(string $id): Backup
    {
        $backup = $this->provider->destination()
            ->backups()
            ->first(fn (Backup $backup): bool => basename($backup->path()) === $id);

        abort_if($backup === null, 404, __('Backup not found.'));

        return $backup;
    }
}
