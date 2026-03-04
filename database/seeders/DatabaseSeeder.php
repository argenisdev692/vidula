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
        $modules = ['USERS', 'PRODUCTS', 'CLIENTS', 'STUDENTS'];
        $actions = ['VIEW ANY', 'VIEW', 'CREATE', 'UPDATE', 'DELETE', 'RESTORE', 'FORCE DELETE'];

        foreach ($modules as $module) {
            foreach ($actions as $action) {
                \Spatie\Permission\Models\Permission::firstOrCreate(
                    ['name' => "{$action} {$module}", 'guard_name' => 'web'],
                    ['uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString()]
                );
            }
        }

        /** @var \Spatie\Permission\Models\Role|null $superAdmin */
        $superAdmin = \Spatie\Permission\Models\Role::where('name', 'SUPER_ADMIN')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo(\Spatie\Permission\Models\Permission::all());
        }

        // NEW MODULES DATA
        $this->call(ProductSeeder::class);
        $this->call(ClientSeeder::class);
        $this->call(StudentSeeder::class);
    }
}
