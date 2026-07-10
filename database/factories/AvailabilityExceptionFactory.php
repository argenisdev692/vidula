<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Availability\Domain\ValueObjects\ExceptionSource;
use Modules\Availability\Infrastructure\Persistence\Eloquent\Models\AvailabilityExceptionEloquentModel;

/**
 * @extends Factory<AvailabilityExceptionEloquentModel>
 */
final class AvailabilityExceptionFactory extends Factory
{
    /**
     * @var class-string<AvailabilityExceptionEloquentModel>
     */
    protected $model = AvailabilityExceptionEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid7(),
            'date' => $this->faker->unique()->dateTimeBetween('now', '+1 year')->format('Y-m-d'),
            'is_available' => false, // default: a closure
            'start_time' => null,
            'end_time' => null,
            'reason' => $this->faker->sentence(3),
            'source' => ExceptionSource::Manual,
        ];
    }

    public function on(string $date): self
    {
        return $this->state(fn (): array => ['date' => $date]);
    }

    /**
     * A system-materialised national holiday (subject to the country sync).
     */
    public function holiday(): self
    {
        return $this->state(fn (): array => [
            'source' => ExceptionSource::Holiday,
        ]);
    }

    public function open(string $start = '10:00', string $end = '14:00'): self
    {
        return $this->state(fn (): array => [
            'is_available' => true,
            'start_time' => $start,
            'end_time' => $end,
        ]);
    }
}
