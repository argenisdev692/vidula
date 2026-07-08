<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Authorization\Infrastructure\Persistence\Eloquent\Models\Role;

/**
 * @extends Factory<Role>
 */
final class RoleFactory extends Factory
{
    /**
     * @var class-string<Role>
     */
    protected $model = Role::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid7(),
            'name' => mb_strtoupper($this->faker->unique()->word()).'_ROLE',
            'guard_name' => 'web',
        ];
    }
}
