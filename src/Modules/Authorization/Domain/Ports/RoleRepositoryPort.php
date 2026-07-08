<?php

declare(strict_types=1);

namespace Modules\Authorization\Domain\Ports;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Authorization\Application\DTOs\RoleFilterData;
use Modules\Authorization\Infrastructure\Persistence\Eloquent\Models\Role;

interface RoleRepositoryPort
{
    /**
     * @return LengthAwarePaginator<int, Role>
     */
    public function paginate(RoleFilterData $filters, int $perPage): LengthAwarePaginator;

    public function findByUuid(string $uuid): ?Role;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Role;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Role $role, array $attributes): Role;

    /**
     * Replace the role's permission grants with the given permission names.
     *
     * @param  array<int, string>  $permissionNames
     */
    public function syncPermissions(Role $role, array $permissionNames): Role;

    public function softDelete(string $uuid): bool;

    public function restore(string $uuid): bool;

    /**
     * @param  array<int, string>  $uuids
     */
    public function bulkSoftDeleteByUuid(array $uuids): int;

    /**
     * @param  array<int, string>  $uuids
     */
    public function bulkRestoreByUuid(array $uuids): int;
}
