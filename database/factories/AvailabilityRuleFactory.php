<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Availability\Infrastructure\Persistence\Eloquent\Models\AvailabilityRuleEloquentModel;

/**
 * @extends Factory<AvailabilityRuleEloquentModel>
 */
final class AvailabilityRuleFactory extends Factory
{
    /**
     * @var class-string<AvailabilityRuleEloquentModel>
     */
    protected $model = AvailabilityRuleEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid7(),
            'day_of_week' => $this->faker->numberBetween(1, 5), // Mon–Fri
            'start_time' => '09:00',
            'end_time' => '13:00',
            'is_available' => true,
        ];
    }

    public function forDay(int $dayOfWeek): self
    {
        return $this->state(fn (): array => ['day_of_week' => $dayOfWeek]);
    }

    public function slot(string $start, string $end): self
    {
        return $this->state(fn (): array => ['start_time' => $start, 'end_time' => $end]);
    }

    public function unavailable(): self
    {
        return $this->state(fn (): array => ['is_available' => false]);
    }
}
