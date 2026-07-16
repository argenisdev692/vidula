<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Meeting\Domain\ValueObjects\MeetingStatus;
use Modules\Meeting\Infrastructure\Persistence\Eloquent\Models\MeetingEloquentModel;

/**
 * @extends Factory<MeetingEloquentModel>
 */
final class MeetingFactory extends Factory
{
    /**
     * @var class-string<MeetingEloquentModel>
     */
    protected $model = MeetingEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = now()->addWeekday()->setTime(10, 0);

        return [
            'uuid' => (string) Str::uuid7(),
            'organizer_id' => User::factory(),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->optional()->paragraph(),
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->addHour(),
            'status' => MeetingStatus::Scheduled,
        ];
    }

    public function cancelled(): self
    {
        return $this->state(fn (): array => ['status' => MeetingStatus::Cancelled]);
    }
}
