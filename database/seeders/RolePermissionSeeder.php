<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Foundation roles + permissions (web guard).
     *
     * Runs BEFORE UserSeeder so roles exist when users are assigned.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['SUPER_ADMIN', 'ADMIN', 'MODERATOR', 'USER', 'GUEST'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        // Permission name format: "{ACTION}_{MODULE}" (e.g. "VIEW_ANY_USERS", "BULK_DELETE_USERS").
        $modules = ['USERS', 'ROLES', 'PERMISSIONS', 'COMPANY_DATA'];
        $actions = [
            'VIEW_ANY',
            'VIEW',
            'CREATE',
            'UPDATE',
            'DELETE',
            'RESTORE',
            'FORCE_DELETE',
            'BULK_DELETE',
            'BULK_RESTORE',
        ];

        foreach ($modules as $module) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name' => "{$action}_{$module}",
                    'guard_name' => 'web',
                ]);
            }
        }

        // SUPER_ADMIN holds every permission.
        $superAdmin = Role::query()
            ->where('name', 'SUPER_ADMIN')
            ->where('guard_name', 'web')
            ->first();

        $superAdmin?->syncPermissions(Permission::where('guard_name', 'web')->get());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
