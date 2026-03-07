<?php

declare(strict_types=1);

namespace Modules\Roles\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Modules\Permissions\Infrastructure\Persistence\Eloquent\Models\PermissionEloquentModel;
use Ramsey\Uuid\Uuid;
use Spatie\Permission\PermissionRegistrar;

final class RolesPermissionsSeeder extends Seeder
{
    private const PERMISSIONS = [
        'VIEW_ROLES',
        'CREATE_ROLES',
        'UPDATE_ROLES',
        'DELETE_ROLES',
    ];

    private const GUARDS = ['web', 'sanctum'];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name) {
            foreach (self::GUARDS as $guard) {
                PermissionEloquentModel::firstOrCreate(
                    ['name' => $name, 'guard_name' => $guard],
                    ['uuid' => Uuid::uuid4()->toString()]
                );
            }
        }
    }
}
