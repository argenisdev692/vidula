<?php

declare(strict_types=1);

namespace Modules\Roles\Application\Commands\UpdateRole;

use Modules\Roles\Domain\Entities\Role;
use Modules\Roles\Domain\Exceptions\RoleNotFoundException;
use Modules\Roles\Domain\Ports\RoleRepositoryPort;
use Shared\Infrastructure\Audit\AuditInterface;

final readonly class UpdateRoleHandler
{
    public function __construct(
        private RoleRepositoryPort $repository,
        private AuditInterface $audit,
    ) {
    }

    public function handle(UpdateRoleCommand $command): Role
    {
        $existing = $this->repository->findByUuid($command->uuid);

        if ($existing === null) {
            throw RoleNotFoundException::forUuid($command->uuid);
        }

        $dto = $command->dto;
        $role = $this->repository->update(
            $command->uuid,
            array_filter([
                'name' => $dto->name,
                'guard_name' => $dto->guardName,
            ], static fn (mixed $value): bool => $value !== null),
            $dto->permissions ?? $existing->permissions,
        );

        $this->audit->log(
            logName: 'roles.updated',
            description: "Role updated: {$role->name}",
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
