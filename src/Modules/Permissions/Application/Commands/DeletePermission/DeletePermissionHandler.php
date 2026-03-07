<?php

declare(strict_types=1);

namespace Modules\Permissions\Application\Commands\DeletePermission;

use Illuminate\Support\Facades\Cache;
use Modules\Permissions\Domain\Exceptions\PermissionNotFoundException;
use Modules\Permissions\Domain\Ports\PermissionRepositoryPort;
use Shared\Infrastructure\Audit\AuditInterface;

final readonly class DeletePermissionHandler
{
    public function __construct(
        private PermissionRepositoryPort $repository,
        private AuditInterface $audit,
    ) {
    }

    public function handle(DeletePermissionCommand $command): void
    {
        $existing = $this->repository->findByUuid($command->uuid);

        if (!$existing) {
            throw PermissionNotFoundException::forUuid($command->uuid);
        }

        $this->repository->delete($command->uuid);
        Cache::forget("permissions_{$command->uuid}");

        $this->audit->log('system.permissions', 'permission.deleted', [
            'uuid' => $existing->uuid,
            'name' => $existing->name,
            'guard_name' => $existing->guardName,
        ]);
    }
}
