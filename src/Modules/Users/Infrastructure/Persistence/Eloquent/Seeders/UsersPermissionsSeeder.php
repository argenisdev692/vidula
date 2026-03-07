<?php

declare(strict_types=1);

namespace Modules\Users\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Modules\Permissions\Infrastructure\Persistence\Eloquent\Models\PermissionEloquentModel;
use Spatie\Permission\Models\Role;

/**
 * UsersPermissionsSeeder — Creates roles + permissions for the Users module.
 *
 * ── Naming Convention ──
 * Role:       Users
 * Permissions: VIEW_USERS, CREATE_USERS, UPDATE_USERS, DELETE_USERS, RESTORE_USERS
 */
final class UsersPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ── Permissions ──
        $permissions = [
            'VIEW_USERS',
            'CREATE_USERS',
            'UPDATE_USERS',
            'DELETE_USERS',
            'RESTORE_USERS',
        ];

        foreach ($permissions as $permission) {
            PermissionEloquentModel::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // ── Role ──
        $role = Role::firstOrCreate(['name' => 'Users', 'guard_name' => 'web']);
        $role->syncPermissions($permissions);

        // ── Also give Super Admin all permissions ──
        $superAdmin = Role::firstOrCreate(['name' => 'SUPER_ADMIN', 'guard_name' => 'web']);
        $superAdmin->givePermissionTo($permissions);
    }
}
