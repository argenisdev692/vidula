<?php

declare(strict_types=1);

namespace Modules\Permissions\Domain\Ports;

use Modules\Permissions\Domain\Entities\Permission;

interface PermissionRepositoryPort
{
    public function findByUuid(string $uuid): ?Permission;

    public function findAllPaginated(array $filters = [], int $page = 1, int $perPage = 15): array;

    public function create(array $data, array $roleNames = []): Permission;

    public function update(string $uuid, array $data, array $roleNames = []): Permission;

    public function delete(string $uuid): void;

    public function listRoles(?string $guardName = 'web'): array;
}
