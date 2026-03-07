<?php

declare(strict_types=1);

namespace Modules\Roles\Application\Commands\CreateRole;

use Modules\Roles\Domain\Entities\Role;
use Modules\Roles\Domain\Ports\RoleRepositoryPort;
use Shared\Infrastructure\Audit\AuditInterface;

final readonly class CreateRoleHandler
{
    public function __construct(
        private RoleRepositoryPort $repository,
        private AuditInterface $audit,
    ) {
    }

    public function handle(CreateRoleCommand $command): Role
    {
        $dto = $command->dto;
        $role = $this->repository->create([
            'name' => $dto->name,
            'guard_name' => $dto->guardName,
        ], $dto->permissions);

        $this->audit->log(
            logName: 'roles.created',
            description: "Role created: {$role->name}",
            properties: [
                'uuid' => $role->uuid,
                'name' => $role->name,
                'guard_name' => $role->guardName,
                'permissions' => $role->permissions,
            ],
        );

        return $role;
    }
}
