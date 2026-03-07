<?php

declare(strict_types=1);

namespace Modules\Permissions\Infrastructure\Persistence\Mappers;

use Modules\Permissions\Domain\Entities\Permission;
use Modules\Permissions\Domain\ValueObjects\PermissionId;
use Modules\Permissions\Infrastructure\Persistence\Eloquent\Models\PermissionEloquentModel;

final class PermissionMapper
{
    public static function toDomain(PermissionEloquentModel $model): Permission
    {
        return new Permission(
            id: new PermissionId((int) $model->getAttribute('id')),
            uuid: (string) $model->getAttribute('uuid'),
            name: (string) $model->getAttribute('name'),
            guardName: (string) $model->getAttribute('guard_name'),
            roles: $model->relationLoaded('roles') ? $model->roles->pluck('name')->values()->all() : [],
            rolesCount: (int) ($model->getAttribute('roles_count') ?? 0),
            createdAt: $model->getAttribute('created_at')?->toIso8601String(),
            updatedAt: $model->getAttribute('updated_at')?->toIso8601String(),
        );
    }
}
