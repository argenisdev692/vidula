<?php

declare(strict_types=1);

namespace Modules\Products\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Modules\Permissions\Infrastructure\Persistence\Eloquent\Models\PermissionEloquentModel;
use Ramsey\Uuid\Uuid;
use Spatie\Permission\PermissionRegistrar;

/**
 * Idempotent seeder for Products module permissions.
 *
 * Convention matches the project-wide pattern in DatabaseSeeder:
 *   "{ACTION} {MODULE}" (e.g., "VIEW PRODUCTS")
 *
 * forgetCachedPermissions() is ALWAYS called first (§10).
 */
final class ProductPermissionsSeeder extends Seeder
{
    /** @var list<string> */
    private const PERMISSIONS = [
        'VIEW_PRODUCTS',
        'CREATE_PRODUCTS',
        'UPDATE_PRODUCTS',
        'DELETE_PRODUCTS',
        'RESTORE_PRODUCTS',
    ];

    private const GUARDS = ['web', 'sanctum'];

    public function run(): void
    {
        // §10 — MUST call forgetCachedPermissions() BEFORE creating permissions
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
