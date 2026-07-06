<?php

declare(strict_types=1);

namespace Modules\Backup\Application\Commands;

use Modules\Backup\Application\Queries\FindBackupHandler;
use Shared\Domain\Ports\AuditPort;

/**
 * Deletes a single backup archive, resolved by basename against the live list
 * (via {@see FindBackupHandler}). The deletion is meta-audited — removing a
 * backup is a sensitive, destructive action.
 */
final readonly class DeleteBackupHandler
{
    public function __construct(
        private FindBackupHandler $find,
        private AuditPort $audit,
    ) {}

    public function handle(string $id, ?object $causer = null): void
    {
        $backup = $this->find->handle($id);
        $path = $backup->path();

        $backup->delete();

        $this->audit->log('backup.deleted', null, ['path' => $path], $causer, 'backup');
    }
}
