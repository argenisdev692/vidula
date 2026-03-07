<?php

declare(strict_types=1);

namespace Modules\Permissions\Infrastructure\Persistence\Repositories;

use Modules\Permissions\Domain\Entities\Permission;
use Modules\Permissions\Domain\Ports\PermissionRepositoryPort;
use Modules\Permissions\Infrastructure\Persistence\Eloquent\Models\PermissionEloquentModel;
use Modules\Permissions\Infrastructure\Persistence\Mappers\PermissionMapper;
use Modules\Roles\Infrastructure\Persistence\Eloquent\Models\RoleEloquentModel;
use Ramsey\Uuid\Uuid;
use Spatie\Permission\PermissionRegistrar;

final class EloquentPermissionRepository implements PermissionRepositoryPort
{
    private const SELECT_COLUMNS = [
        'id',
        'uuid',
        'name',
        'guard_name',
        'created_at',
        'updated_at',
    ];

    public function findByUuid(string $uuid): ?Permission
    {
        $model = PermissionEloquentModel::query()
            ->select(self::SELECT_COLUMNS)
            ->with(['roles:id,uuid,name,guard_name'])
            ->withCount('roles')
            ->where('uuid', $uuid)
            ->first();

        return $model ? PermissionMapper::toDomain($model) : null;
    }

    public function findAllPaginated(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $query = PermissionEloquentModel::query()
            ->select(self::SELECT_COLUMNS)
            ->with(['roles:id,uuid,name,guard_name'])
            ->withCount('roles')
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
                static fn (PermissionEloquentModel $model): Permission => PermissionMapper::toDomain($model),
                $paginator->items(),
            ),
            'total' => $paginator->total(),
            'perPage' => $paginator->perPage(),
            'currentPage' => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
        ];
    }

    public function create(array $data, array $roleNames = []): Permission
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $model = PermissionEloquentModel::query()->create([
            'uuid' => $data['uuid'] ?? Uuid::uuid4()->toString(),
            'name' => $data['name'],
            'guard_name' => $data['guard_name'] ?? 'web',
        ]);

        $model->syncRoles($roleNames);
        $model->load(['roles:id,uuid,name,guard_name'])->loadCount('roles');

        return PermissionMapper::toDomain($model);
    }

    public function update(string $uuid, array $data, array $roleNames = []): Permission
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $model = PermissionEloquentModel::query()->where('uuid', $uuid)->firstOrFail();
        $model->update($data);
        $model->syncRoles($roleNames);
        $model->refresh();
        $model->load(['roles:id,uuid,name,guard_name'])->loadCount('roles');

        return PermissionMapper::toDomain($model);
    }

    public function delete(string $uuid): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $model = PermissionEloquentModel::query()->where('uuid', $uuid)->firstOrFail();
        $model->delete();
    }

    public function listRoles(?string $guardName = 'web'): array
    {
        return RoleEloquentModel::query()
            ->select(['uuid', 'name', 'guard_name'])
            ->when($guardName, fn ($builder, $value) => $builder->where('guard_name', $value))
            ->orderBy('name')
            ->get()
            ->map(static fn (RoleEloquentModel $role): array => [
                'uuid' => $role->uuid,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
            ])
            ->values()
            ->all();
    }
}
