<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Factories\ProductFactory;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        ProductFactory::new()->count(30)->create();
    }
}
