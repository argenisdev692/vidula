<?php

declare(strict_types=1);

namespace Modules\Backup\Application\Commands;

use Illuminate\Support\Facades\Artisan;
use Shared\Domain\Ports\AuditPort;

/**
 * Queues an on-demand `backup:run` so the (potentially slow) archive job runs on
 * a Horizon worker rather than blocking the request. The trigger is meta-audited
 * so the trail records who asked for a manual backup.
 */
final readonly class RunBackupHandler
{
    public function __construct(private AuditPort $audit) {}

    public function handle(?object $causer = null): void
    {
        Artisan::queue('backup:run');

        $this->audit->log('backup.run_requested', null, ['queued' => true], $causer, 'backup');
    }
}
