<?php

declare(strict_types=1);

namespace Modules\Roles\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphedByMany;
use Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserEloquentModel;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Models\Role as SpatieRole;

final class RoleEloquentModel extends SpatieRole
{
    use HasFactory;
    use LogsActivity;

    protected $table = 'roles';

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
            ->useLogName('system.roles');
    }

    public function users(): MorphedByMany
    {
        return $this->morphedByMany(
            UserEloquentModel::class,
            'model',
            config('permission.table_names.model_has_roles'),
            config('permission.column_names.role_pivot_key') ?: 'role_id',
            config('permission.column_names.model_morph_key')
        );
    }
}
