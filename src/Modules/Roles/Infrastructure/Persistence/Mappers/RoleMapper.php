<?php

declare(strict_types=1);

namespace Modules\Roles\Infrastructure\Persistence\Mappers;

use Modules\Roles\Domain\Entities\Role;
use Modules\Roles\Domain\ValueObjects\RoleId;
use Modules\Roles\Infrastructure\Persistence\Eloquent\Models\RoleEloquentModel;

final class RoleMapper
{
    public static function toDomain(RoleEloquentModel $model): Role
    {
        return new Role(
            id: new RoleId($model->id),
            uuid: $model->uuid,
            name: $model->name,
            guardName: $model->guard_name,
            permissions: $model->permissions->pluck('name')->values()->all(),
            usersCount: (int) ($model->users_count ?? $model->users()->count()),
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }
}
