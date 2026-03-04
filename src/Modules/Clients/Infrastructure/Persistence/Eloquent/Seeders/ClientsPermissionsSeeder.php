<?php

declare(strict_types=1);

namespace Modules\Clients\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * ClientsPermissionsSeeder — Creates roles + permissions for the Clients module.
 *
 * ── Naming Convention ──
 * Role:       Clients
 * Permissions: VIEW_CLIENTS, CREATE_CLIENTS, UPDATE_CLIENTS, DELETE_CLIENTS
 */
final class ClientsPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ── Permissions ──
        $permissions = [
            'VIEW_CLIENTS',
            'CREATE_CLIENTS',
            'UPDATE_CLIENTS',
            'DELETE_CLIENTS',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // ── Role ──
        $role = Role::firstOrCreate(['name' => 'Clients', 'guard_name' => 'web']);
        $role->syncPermissions($permissions);

        // ── Also give Super Admin all permissions ──
        $superAdmin = Role::firstOrCreate(['name' => 'SUPER_ADMIN', 'guard_name' => 'web']);
        $superAdmin->givePermissionTo($permissions);
    }
}
