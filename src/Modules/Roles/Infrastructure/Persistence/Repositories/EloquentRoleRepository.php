<?php

declare(strict_types=1);

namespace Modules\Roles\Infrastructure\Persistence\Repositories;

use Modules\Permissions\Infrastructure\Persistence\Eloquent\Models\PermissionEloquentModel;
use Modules\Roles\Domain\Entities\Role;
use Modules\Roles\Domain\Exceptions\ProtectedRoleException;
use Modules\Roles\Domain\Ports\RoleRepositoryPort;
use Modules\Roles\Infrastructure\Persistence\Eloquent\Models\RoleEloquentModel;
use Modules\Roles\Infrastructure\Persistence\Mappers\RoleMapper;
use Ramsey\Uuid\Uuid;
use Spatie\Permission\PermissionRegistrar;

final class EloquentRoleRepository implements RoleRepositoryPort
{
    private const SELECT_COLUMNS = [
        'id',
        'uuid',
        'name',
        'guard_name',
        'created_at',
        'updated_at',
    ];

    public function findByUuid(string $uuid): ?Role
    {
        $model = RoleEloquentModel::query()
            ->select(self::SELECT_COLUMNS)
            ->with(['permissions:id,name,guard_name'])
            ->withCount('users')
            ->where('uuid', $uuid)
            ->first();

        return $model ? RoleMapper::toDomain($model) : null;
    }

    public function findAllPaginated(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $query = RoleEloquentModel::query()
            ->select(self::SELECT_COLUMNS)
            ->with(['permissions:id,name,guard_name'])
            ->withCount('users')
            ->when(
                $filters['search'] ?? null,
                fn ($builder, $search) => $builder->where('name', 'like', "%{$search}%")
            )
            ->when(
                $filters['guardName'] ?? $filters['guard_name'] ?? null,
                fn ($builder, $guardName) => $builder->where('guard_name', $guardName)
            )
            ->orderBy(
                $filters['sortBy'] ?? $filters['sort_by'] ?? 'created_at',
                $filters['sortDir'] ?? $filters['sort_dir'] ?? 'desc',
            );

        $paginator = $query->paginate(perPage: $perPage, page: $page);

        return [
            'data' => array_map(
                static fn (RoleEloquentModel $model): Role => RoleMapper::toDomain($model),
                $paginator->items(),
            ),
            'total' => $paginator->total(),
            'perPage' => $paginator->perPage(),
            'currentPage' => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
        ];
    }

    public function create(array $data, array $permissionNames = []): Role
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $model = RoleEloquentModel::query()->create([
            'uuid' => $data['uuid'] ?? Uuid::uuid4()->toString(),
            'name' => $data['name'],
            'guard_name' => $data['guard_name'] ?? 'web',
        ]);

        if ($permissionNames !== []) {
            $model->syncPermissions($permissionNames);
        } else {
            $model->syncPermissions([]);
        }

        $model->load(['permissions:id,name,guard_name'])->loadCount('users');

        return RoleMapper::toDomain($model);
    }

    public function update(string $uuid, array $data, array $permissionNames = []): Role
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $model = RoleEloquentModel::query()->where('uuid', $uuid)->firstOrFail();
        $model->update($data);

        if ($permissionNames !== []) {
            $model->syncPermissions($permissionNames);
        } else {
            $model->syncPermissions([]);
        }

        $model->refresh();
        $model->load(['permissions:id,name,guard_name'])->loadCount('users');

        return RoleMapper::toDomain($model);
    }

    public function delete(string $uuid): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $model = RoleEloquentModel::query()->where('uuid', $uuid)->firstOrFail();

        if (in_array($model->name, ['SUPER_ADMIN'], true)) {
            throw ProtectedRoleException::cannotDelete($model->name);
        }

        $model->delete();
    }

    public function listPermissions(?string $guardName = 'web'): array
    {
        return PermissionEloquentModel::query()
            ->select(['uuid', 'name', 'guard_name'])
            ->when($guardName, fn ($builder, $value) => $builder->where('guard_name', $value))
            ->orderBy('name')
            ->get()
            ->map(static fn (PermissionEloquentModel $permission): array => [
                'uuid' => $permission->uuid,
                'name' => $permission->name,
                'guard_name' => $permission->guard_name,
            ])
            ->values()
            ->all();
    }
}
