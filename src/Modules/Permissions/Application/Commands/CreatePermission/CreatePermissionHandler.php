<?php

declare(strict_types=1);

namespace Modules\Permissions\Application\Commands\CreatePermission;

use Modules\Permissions\Domain\Entities\Permission;
use Modules\Permissions\Domain\Ports\PermissionRepositoryPort;
use Shared\Infrastructure\Audit\AuditInterface;

final readonly class CreatePermissionHandler
{
    public function __construct(
        private PermissionRepositoryPort $repository,
        private AuditInterface $audit,
    ) {
    }

    public function handle(CreatePermissionCommand $command): Permission
    {
        $permission = $this->repository->create(
            data: [
                'name' => $command->dto->name,
                'guard_name' => $command->dto->guardName,
            ],
            roleNames: $command->dto->roles,
        );

        $this->audit->log('system.permissions', 'permission.created', [
            'uuid' => $permission->uuid,
            'name' => $permission->name,
            'guard_name' => $permission->guardName,
        ]);

        return $permission;
    }
}
