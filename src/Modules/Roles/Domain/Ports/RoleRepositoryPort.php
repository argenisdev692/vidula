<?php

declare(strict_types=1);

namespace Modules\Roles\Domain\Ports;

use Modules\Roles\Domain\Entities\Role;

interface RoleRepositoryPort
{
    public function findByUuid(string $uuid): ?Role;

    /**
     * @param array<string, mixed> $filters
     * @return array{data: list<Role>, total: int, perPage: int, currentPage: int, lastPage: int}
     */
    public function findAllPaginated(array $filters = [], int $page = 1, int $perPage = 15): array;

    /**
     * @param array<string, mixed> $data
     * @param list<string> $permissionNames
     */
    public function create(array $data, array $permissionNames = []): Role;

    /**
     * @param array<string, mixed> $data
     * @param list<string> $permissionNames
     */
    public function update(string $uuid, array $data, array $permissionNames = []): Role;

    public function delete(string $uuid): void;

    /**
     * @return list<array{uuid: string, name: string, guard_name: string}>
     */
    public function listPermissions(?string $guardName = 'web'): array;
}
