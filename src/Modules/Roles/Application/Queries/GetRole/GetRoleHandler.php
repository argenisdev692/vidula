<?php

declare(strict_types=1);

namespace Modules\Roles\Application\Queries\GetRole;

use Illuminate\Support\Facades\Cache;
use Modules\Roles\Application\Queries\ReadModels\PermissionOptionReadModel;
use Modules\Roles\Application\Queries\ReadModels\RoleReadModel;
use Modules\Roles\Domain\Exceptions\RoleNotFoundException;
use Modules\Roles\Domain\Ports\RoleRepositoryPort;

final readonly class GetRoleHandler
{
    public function __construct(
        private RoleRepositoryPort $repository,
    ) {
    }

    public function handle(GetRoleQuery $query): RoleReadModel
    {
        $cacheKey = "role_read_{$query->uuid}";
        $ttl = 60 * 15;

        return Cache::remember($cacheKey, $ttl, function () use ($query): RoleReadModel {
            $role = $this->repository->findByUuid($query->uuid);

            if ($role === null) {
                throw RoleNotFoundException::forUuid($query->uuid);
            }

            $availablePermissions = array_map(
                static fn (array $permission): PermissionOptionReadModel => new PermissionOptionReadModel(
                    uuid: $permission['uuid'],
                    name: $permission['name'],
                    guardName: $permission['guard_name'],
                ),
                $this->repository->listPermissions($role->guardName),
            );

            return new RoleReadModel(
                uuid: $role->uuid,
                name: $role->name,
                guardName: $role->guardName,
                permissions: $role->permissions,
                usersCount: $role->usersCount,
                createdAt: $role->createdAt,
                updatedAt: $role->updatedAt,
                availablePermissions: $availablePermissions,
            );
        });
    }
}
