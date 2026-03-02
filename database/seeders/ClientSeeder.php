<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Factories\ClientFactory;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        ClientFactory::new()->count(30)->create();
    }
}
