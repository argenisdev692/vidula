<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\ContactSupport\Infrastructure\Persistence\Eloquent\Models\ContactSupportEloquentModel;

/**
 * @extends Factory<ContactSupportEloquentModel>
 */
final class ContactSupportFactory extends Factory
{
    /**
     * @var class-string<ContactSupportEloquentModel>
     */
    protected $model = ContactSupportEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid7(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->numerify('+1##########'),
            'subject' => Str::limit($this->faker->sentence(4), 150, ''),
            'message' => $this->faker->paragraph(),
            'sms_consent' => $this->faker->boolean(),
            'readed' => false,
        ];
    }

    /**
     * Submission that has already been read by an operator.
     */
    public function read(): self
    {
        return $this->state(fn (): array => ['readed' => true]);
    }
}
