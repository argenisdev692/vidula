<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Authorization\Infrastructure\Persistence\Eloquent\Models\Permission;
use Modules\Authorization\Infrastructure\Persistence\Eloquent\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    private const string GUARD = 'web';

    /** @var list<string> */
    private const array ROLES = ['SUPER_ADMIN', 'ADMIN', 'MODERATOR', 'USER', 'GUEST'];

    /**
     * Standard modules — each gets the full action set below.
     * Permission name format: "{ACTION}_{MODULE}" (e.g. "VIEW_ANY_USERS").
     *
     * @var list<string>
     */
    private const array MODULES = ['USERS', 'ROLES', 'PERMISSIONS', 'COMPANY_DATA', 'BLOG_CATEGORIES', 'CONTACT_SUPPORTS'];

    /**
     * Full action set granted to every module (browse, view, write,
     * soft-delete + restore, bulk soft-delete + restore, and export).
     * FORCE_DELETE / BULK_FORCE_DELETE are omitted: no module implements hard
     * deletes yet, so seeding them would only create dead permissions.
     *
     * @var list<string>
     */
    private const array MODULES_ACTIONS = [
        'VIEW_ANY',
        'VIEW',
        'CREATE',
        'UPDATE',
        'DELETE',
        'RESTORE',
        'BULK_DELETE',
        'BULK_RESTORE',
        'EXPORT',
    ];

    /**
     * Access-management permissions on the USERS module. Kept separate from the
     * standard CRUD set because granting access (attaching roles / direct
     * permission top-ups to a user) is far more sensitive than editing a name —
     * holding UPDATE_USERS must NOT imply the ability to elevate privileges.
     * Enforced at the route and, defence-in-depth, in SyncUserAccessHandler.
     *
     * @var list<string>
     */
    private const array USER_ACCESS_ACTIONS = ['ASSIGN_ROLES', 'ASSIGN_PERMISSIONS'];

    /**
     * Read-only modules (immutable audit trail): only browse / view / export.
     * No create/update/delete — the activity log is trimmed by cron, not the UI.
     *
     * @var list<string>
     */
    private const array READ_ONLY_MODULES = ['ACTIVITY_LOGS'];

    /** @var list<string> */
    private const array READ_ONLY_ACTIONS = ['VIEW_ANY', 'VIEW', 'EXPORT'];

    /**
     * Backups panel (spatie/laravel-backup): list, download an archive, run a
     * backup on demand, delete an archive. No update — a backup is immutable.
     *
     * @var list<string>
     */
    private const array BACKUP_ACTIONS = ['VIEW_ANY', 'DOWNLOAD', 'CREATE', 'DELETE'];

    /**
     * Foundation roles + permissions (web guard).
     *
     * Runs BEFORE UserSeeder so roles exist when users are assigned.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->ensureRoles();
        $this->ensurePermissions($this->permissionNames());
        $this->grantAllToSuperAdmin();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function ensureRoles(): void
    {
        foreach (self::ROLES as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => self::GUARD]);
        }
    }

    /**
     * All permission names to seed, de-duplicated.
     *
     * @return list<string>
     */
    private function permissionNames(): array
    {
        $names = [
            ...$this->matrix(self::MODULES, self::MODULES_ACTIONS),
            ...$this->matrix(['USERS'], self::USER_ACCESS_ACTIONS),
            ...$this->matrix(self::READ_ONLY_MODULES, self::READ_ONLY_ACTIONS),
            ...$this->matrix(['BACKUPS'], self::BACKUP_ACTIONS),
        ];

        return array_values(array_unique($names));
    }

    /**
     * Cartesian product of modules × actions → "{ACTION}_{MODULE}".
     *
     * @param  list<string>  $modules
     * @param  list<string>  $actions
     * @return list<string>
     */
    private function matrix(array $modules, array $actions): array
    {
        $names = [];

        foreach ($modules as $module) {
            foreach ($actions as $action) {
                $names[] = "{$action}_{$module}";
            }
        }

        return $names;
    }

    /**
     * @param  list<string>  $names
     */
    private function ensurePermissions(array $names): void
    {
        foreach ($names as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => self::GUARD]);
        }
    }

    /**
     * SUPER_ADMIN holds every permission.
     */
    private function grantAllToSuperAdmin(): void
    {
        Role::query()
            ->where('name', 'SUPER_ADMIN')
            ->where('guard_name', self::GUARD)
            ->first()
            ?->syncPermissions(Permission::where('guard_name', self::GUARD)->get());
    }
}
