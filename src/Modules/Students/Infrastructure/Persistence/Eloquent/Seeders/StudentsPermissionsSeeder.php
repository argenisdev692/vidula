<?php

declare(strict_types=1);

namespace Modules\Students\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Modules\Permissions\Infrastructure\Persistence\Eloquent\Models\PermissionEloquentModel;
use Ramsey\Uuid\Uuid;
use Spatie\Permission\PermissionRegistrar;

final class StudentsPermissionsSeeder extends Seeder
{
    private const PERMISSIONS = [
        'VIEW_STUDENTS',
        'CREATE_STUDENTS',
        'UPDATE_STUDENTS',
        'DELETE_STUDENTS',
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
