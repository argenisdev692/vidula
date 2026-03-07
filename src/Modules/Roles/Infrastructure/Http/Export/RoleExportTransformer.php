<?php

declare(strict_types=1);

namespace Modules\Roles\Infrastructure\Http\Export;

use Modules\Roles\Infrastructure\Persistence\Eloquent\Models\RoleEloquentModel;

final class RoleExportTransformer
{
    #[\NoDiscard]
    public static function transform(RoleEloquentModel $role): array
    {
        return $role
            |> self::extractBaseData(...)
            |> self::sanitizeOutput(...);
    }

    private static function extractBaseData(RoleEloquentModel $role): array
    {
        return [
            'uuid' => $role->uuid,
            'name' => $role->name,
            'guard_name' => $role->guard_name,
            'permissions' => $role->permissions->pluck('name')->implode(', '),
            'users_count' => (string) $role->users_count,
            'created_at' => $role->created_at?->format('F j, Y') ?? '',
        ];
    }

    private static function sanitizeOutput(array $data): array
    {
        return array_map(static fn (mixed $value): string => (string) ($value ?? ''), $data);
    }
}
