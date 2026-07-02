<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Seed the default application users.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@vidula.test'],
            [
                'name' => 'Admin',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        User::factory(5)->create();
    }
}
