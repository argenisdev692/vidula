<?php

namespace Database\Factories;

use Modules\CompanyData\Infrastructure\Persistence\Eloquent\Models\CompanyDataEloquentModel;
use Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CompanyDataFactory extends Factory
{
    protected $model = CompanyDataEloquentModel::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'user_id' => UserEloquentModel::factory(),
            'name' => $this->faker->name(),
            'company_name' => $this->faker->company(),
            'email' => $this->faker->unique()->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'website' => $this->faker->url(),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
        ];
    }
}
