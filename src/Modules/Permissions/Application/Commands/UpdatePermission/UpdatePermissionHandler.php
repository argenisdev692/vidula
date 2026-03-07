<?php

declare(strict_types=1);

namespace Modules\Permissions\Application\Commands\UpdatePermission;

use Illuminate\Support\Facades\Cache;
use Modules\Permissions\Domain\Entities\Permission;
use Modules\Permissions\Domain\Exceptions\PermissionNotFoundException;
use Modules\Permissions\Domain\Ports\PermissionRepositoryPort;
use Shared\Infrastructure\Audit\AuditInterface;

final readonly class UpdatePermissionHandler
{
    public function __construct(
        private PermissionRepositoryPort $repository,
        private AuditInterface $audit,
    ) {
    }

    public function handle(UpdatePermissionCommand $command): Permission
    {
        $existing = $this->repository->findByUuid($command->uuid);

        if (!$existing) {
            throw PermissionNotFoundException::forUuid($command->uuid);
        }

        $permission = $this->repository->update(
            uuid: $command->uuid,
            data: array_filter([
                'name' => $command->dto->name,
                'guard_name' => $command->dto->guardName,
            ], static fn (mixed $value): bool => $value !== null),
            roleNames: $command->dto->roles ?? $existing->roles,
        );

        Cache::forget("permissions_{$command->uuid}");

        $this->audit->log('system.permissions', 'permission.updated', [
            'uuid' => $permission->uuid,
            'name' => $permission->name,
            'guard_name' => $permission->guardName,
        ]);

        return $permission;
    }
}
