<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Clients\Infrastructure\Persistence\Eloquent\Models\ClientEloquentModel;
use Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserEloquentModel;
use Ramsey\Uuid\Uuid;

final class ClientFactory extends Factory
{
    protected $model = ClientEloquentModel::class;

    public function definition(): array
    {
        return [
            'uuid' => Uuid::uuid4()->toString(),
            'user_id' => UserEloquentModel::factory(),
            'client_name' => $this->faker->company(),
            'email' => $this->faker->unique()->safeEmail(),
            'status' => 'ACTIVE',
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'tax_id' => $this->faker->regexify('[0-9]{9}[A-Z]'),
            'nif' => $this->faker->regexify('[0-9]{8}[A-Z]'),
            'website' => 'https://example.com',
            'facebook_link' => 'https://facebook.com/' . $this->faker->userName(),
            'instagram_link' => 'https://instagram.com/' . $this->faker->userName(),
            'linkedin_link' => 'https://linkedin.com/company/' . $this->faker->slug(),
            'twitter_link' => 'https://twitter.com/' . $this->faker->userName(),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'notes' => $this->faker->paragraph(),
        ];
    }
}
