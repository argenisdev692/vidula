<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->call([
            RolePermissionSeeder::class,
            UserSeeder::class,
            CompanySeeder::class,
            ClientSeeder::class,
            BlogCategorySeeder::class,
            ServiceSeeder::class,
            AvailabilitySeeder::class,
            CountryHolidaySeeder::class,
        ]);
    }
}
