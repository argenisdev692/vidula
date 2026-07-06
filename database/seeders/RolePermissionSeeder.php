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

        // Read-only modules (immutable audit trail): only browse / view / export.
        // No create/update/delete — the activity log is trimmed by cron, not the UI.
        $readOnlyModules = ['ACTIVITY_LOGS'];
        $readOnlyActions = ['VIEW_ANY', 'VIEW', 'EXPORT'];

        foreach ($readOnlyModules as $module) {
            foreach ($readOnlyActions as $action) {
                Permission::firstOrCreate([
                    'name' => "{$action}_{$module}",
                    'guard_name' => 'web',
                ]);
            }
        }

        // Backups panel (spatie/laravel-backup): list, download an archive, run a
        // backup on demand, delete an archive. No update — a backup is immutable.
        foreach (['VIEW_ANY', 'DOWNLOAD', 'CREATE', 'DELETE'] as $action) {
            Permission::firstOrCreate([
                'name' => "{$action}_BACKUPS",
                'guard_name' => 'web',
            ]);
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
