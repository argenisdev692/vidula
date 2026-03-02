<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Factories\StudentFactory;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        StudentFactory::new()->count(30)->create();
    }
}
