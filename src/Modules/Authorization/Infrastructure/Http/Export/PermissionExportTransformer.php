<?php

declare(strict_types=1);

namespace Modules\Authorization\Infrastructure\Http\Export;

use Modules\Authorization\Infrastructure\Persistence\Eloquent\Models\Permission;

/**
 * Maps a {@see Permission} row to export columns. See {@see RoleExportTransformer}
 * for the shared export mechanics.
 */
final readonly class PermissionExportTransformer
{
    /**
     * @return array<string, string>
     */
    #[\NoDiscard]
    public static function transformForTable(Permission $permission): array
    {
        return [
            'Name' => $permission->name,
            'Guard' => $permission->guard_name,
            'Roles' => (string) ($permission->roles_count ?? 0),
            'Created' => $permission->created_at?->toDateTimeString() ?? '—',
            'Status' => $permission->deleted_at !== null ? 'Suspended' : 'Active',
        ];
    }

    /**
     * @return array<string, string>
     */
    #[\NoDiscard]
    public static function transformForPdf(Permission $permission): array
    {
        return self::transformForTable($permission);
    }
}
