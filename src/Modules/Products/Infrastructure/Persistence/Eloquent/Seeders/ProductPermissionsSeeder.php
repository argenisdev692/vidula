<?php

declare(strict_types=1);

namespace Modules\Products\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Ramsey\Uuid\Uuid;
use Spatie\Permission\Models\Permission;

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
        'VIEW PRODUCTS',
        'VIEW ANY PRODUCTS',
        'CREATE PRODUCTS',
        'UPDATE PRODUCTS',
        'DELETE PRODUCTS',
        'RESTORE PRODUCTS',
        'FORCE DELETE PRODUCTS',
    ];

    public function run(): void
    {
        // §10 — MUST call forgetCachedPermissions() BEFORE creating permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['uuid' => Uuid::uuid4()->toString()]
            );
        }
    }
}
