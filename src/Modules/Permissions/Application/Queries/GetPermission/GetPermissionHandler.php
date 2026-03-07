<?php

declare(strict_types=1);

namespace Modules\Permissions\Application\Queries\GetPermission;

use Illuminate\Support\Facades\Cache;
use Modules\Permissions\Application\Queries\ReadModels\PermissionReadModel;
use Modules\Permissions\Application\Queries\ReadModels\RoleOptionReadModel;
use Modules\Permissions\Domain\Exceptions\PermissionNotFoundException;
use Modules\Permissions\Domain\Ports\PermissionRepositoryPort;

final readonly class GetPermissionHandler
{
    public function __construct(
        private PermissionRepositoryPort $repository,
    ) {
    }

    public function handle(GetPermissionQuery $query): PermissionReadModel
    {
        return Cache::remember("permissions_{$query->uuid}", 120, function () use ($query): PermissionReadModel {
            $permission = $this->repository->findByUuid($query->uuid);

            if (!$permission) {
                throw PermissionNotFoundException::forUuid($query->uuid);
            }

            return new PermissionReadModel(
                uuid: $permission->uuid,
                name: $permission->name,
                guardName: $permission->guardName,
                roles: $permission->roles,
                rolesCount: $permission->rolesCount,
                createdAt: $permission->createdAt,
                updatedAt: $permission->updatedAt,
                availableRoles: array_map(
                    static fn (array $role): RoleOptionReadModel => new RoleOptionReadModel(
                        uuid: $role['uuid'],
                        name: $role['name'],
                        guardName: $role['guard_name'],
                    ),
                    $this->repository->listRoles($permission->guardName),
                ),
            );
        });
    }
}
