<?php

declare(strict_types=1);

namespace Modules\Permissions\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Roles\Infrastructure\Persistence\Eloquent\Models\RoleEloquentModel;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Models\Permission as SpatiePermission;

final class PermissionEloquentModel extends SpatiePermission
{
    use HasFactory;
    use LogsActivity;

    protected $table = 'permissions';

    protected $fillable = [
        'uuid',
        'name',
        'guard_name',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'uuid',
                'name',
                'guard_name',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('system.permissions');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            related: RoleEloquentModel::class,
            table: config('permission.table_names.role_has_permissions'),
            foreignPivotKey: config('permission.column_names.permission_pivot_key') ?: 'permission_id',
            relatedPivotKey: config('permission.column_names.role_pivot_key') ?: 'role_id',
        );
    }
}
