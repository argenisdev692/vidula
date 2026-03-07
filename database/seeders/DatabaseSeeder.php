<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // SIEMPRE primero
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // User::factory(10)->create();

        // USERS AND ROLES - Call UserSeeder (Refactored)
        $this->call(UserSeeder::class);

        // COMPANY DATA - Call CompanySeeder
        $this->call(CompanySeeder::class);

        // BLOG DATA - Call Blog Seeder
        $this->call(BlogCategorySeeder::class);

        // Permissions for New Modules
        $modules = ['USERS', 'PRODUCTS', 'CLIENTS', 'STUDENTS', 'BLOG_CATEGORIES', 'POSTS'];
        $actions = ['VIEW ANY', 'VIEW', 'CREATE', 'UPDATE', 'DELETE', 'RESTORE', 'FORCE DELETE'];

        foreach ($modules as $module) {
            foreach ($actions as $action) {
                \Modules\Permissions\Infrastructure\Persistence\Eloquent\Models\PermissionEloquentModel::firstOrCreate(
                    ['name' => "{$action} {$module}", 'guard_name' => 'web'],
                    ['uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString()]
                );
                \Modules\Permissions\Infrastructure\Persistence\Eloquent\Models\PermissionEloquentModel::firstOrCreate(
                    ['name' => "{$action} {$module}", 'guard_name' => 'sanctum'],
                    ['uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString()]
                );
            }
        }

        $this->call(\Modules\Permissions\Infrastructure\Persistence\Eloquent\Seeders\PermissionsPermissionsSeeder::class);

        $this->call(\Modules\Roles\Infrastructure\Persistence\Eloquent\Seeders\RolesPermissionsSeeder::class);

        $this->call(\Modules\Products\Infrastructure\Persistence\Eloquent\Seeders\ProductPermissionsSeeder::class);

        $this->call(\Modules\Students\Infrastructure\Persistence\Eloquent\Seeders\StudentsPermissionsSeeder::class);

        $this->call(\Modules\CompanyData\Infrastructure\Persistence\Eloquent\Seeders\CompanyDataPermissionsSeeder::class);

        /** @var \Spatie\Permission\Models\Role|null $superAdmin */
        $superAdmin = \Spatie\Permission\Models\Role::where('name', 'SUPER_ADMIN')->where('guard_name', 'web')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo(\Modules\Permissions\Infrastructure\Persistence\Eloquent\Models\PermissionEloquentModel::where('guard_name', 'web')->get());
        }

        /** @var \Spatie\Permission\Models\Role|null $superAdminSanctum */
        $superAdminSanctum = \Spatie\Permission\Models\Role::where('name', 'SUPER_ADMIN')->where('guard_name', 'sanctum')->first();
        if ($superAdminSanctum) {
            $superAdminSanctum->givePermissionTo(\Modules\Permissions\Infrastructure\Persistence\Eloquent\Models\PermissionEloquentModel::where('guard_name', 'sanctum')->get());
        }

        // NEW MODULES DATA
        $this->call(ProductSeeder::class);
        $this->call(ClientSeeder::class);
        $this->call(StudentSeeder::class);
    }
}
