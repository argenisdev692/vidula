<?php

declare(strict_types=1);

namespace Modules\Permissions\Infrastructure\Http\Export;

use Modules\Permissions\Infrastructure\Persistence\Eloquent\Models\PermissionEloquentModel;

final class PermissionExportTransformer
{
    #[\NoDiscard]
    public static function transform(PermissionEloquentModel $permission): array
    {
        return $permission
            |> self::extractBaseData(...)
            |> self::sanitizeOutput(...);
    }

    private static function extractBaseData(PermissionEloquentModel $permission): array
    {
        return [
            'uuid' => $permission->uuid,
            'name' => $permission->name,
            'guard_name' => $permission->guard_name,
            'roles' => $permission->roles->pluck('name')->implode(', '),
            'roles_count' => (string) $permission->roles_count,
            'created_at' => $permission->created_at?->format('F j, Y') ?? '',
        ];
    }

    private static function sanitizeOutput(array $data): array
    {
        return array_map(static fn (mixed $value): string => (string) ($value ?? ''), $data);
    }
}
