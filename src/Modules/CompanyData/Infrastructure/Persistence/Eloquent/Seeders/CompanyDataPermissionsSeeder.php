<?php

declare(strict_types=1);

namespace Modules\CompanyData\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Modules\Permissions\Infrastructure\Persistence\Eloquent\Models\PermissionEloquentModel;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class CompanyDataPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'VIEW_COMPANY_DATA',
            'CREATE_COMPANY_DATA',
            'UPDATE_COMPANY_DATA',
            'DELETE_COMPANY_DATA',
        ];

        foreach ($permissions as $permission) {
            PermissionEloquentModel::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString()]
            );
            PermissionEloquentModel::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'sanctum'],
                ['uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString()]
            );
        }

        $superAdminWeb = Role::firstOrCreate(
            ['name' => 'SUPER_ADMIN', 'guard_name' => 'web'],
            ['uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString()]
        );
        $superAdminSanctum = Role::firstOrCreate(
            ['name' => 'SUPER_ADMIN', 'guard_name' => 'sanctum'],
            ['uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString()]
        );

        $superAdminWeb->givePermissionTo($permissions);
        $superAdminSanctum->givePermissionTo($permissions);
    }
}
