<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Clients\Infrastructure\Persistence\Eloquent\Models\ClientEloquentModel;

/**
 * @extends Factory<ClientEloquentModel>
 */
final class ClientFactory extends Factory
{
    /**
     * @var class-string<ClientEloquentModel>
     */
    protected $model = ClientEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid7(),
            'user_id' => User::factory(),
            'client_name' => $this->faker->company(),
            'email' => $this->faker->unique()->safeEmail(),
            'status' => 'DRAFT',
            'phone' => '+12015550101',
            'address' => $this->faker->streetAddress(),
            'tax_id' => null,
            'nif' => null,
            'website' => null,
            'facebook_link' => null,
            'instagram_link' => null,
            'linkedin_link' => null,
            'twitter_link' => null,
            'notes' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => ['status' => 'ACTIVE']);
    }

    public function archived(): static
    {
        return $this->state(fn (): array => ['status' => 'ARCHIVED']);
    }
}
