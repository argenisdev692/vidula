<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Appointment\Domain\ValueObjects\ClientType;
use Modules\Appointment\Domain\ValueObjects\MeetingStatus;
use Modules\Appointment\Domain\ValueObjects\ProjectType;
use Modules\Appointment\Domain\ValueObjects\StatusLead;
use Modules\Appointment\Infrastructure\Persistence\Eloquent\Models\AppointmentEloquentModel;

/**
 * @extends Factory<AppointmentEloquentModel>
 */
final class AppointmentFactory extends Factory
{
    /**
     * @var class-string<AppointmentEloquentModel>
     */
    protected $model = AppointmentEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid7(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'client_type' => ClientType::Individual,
            'company_name' => null,
            'project_type' => ProjectType::NewWebsite,
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->numerify('+1##########'),
            'address' => $this->faker->streetAddress(),
            'address_2' => null,
            'zip_code' => $this->faker->postcode(),
            'city' => $this->faker->city(),
            'state' => $this->faker->stateAbbr(),
            'country' => 'United States',
            'country_code' => 'US',
            'latitude' => null,
            'longitude' => null,
            'sms_consent' => $this->faker->boolean(),
            'readed' => false,
            'is_spam' => false,
            'spam_score' => 0,
            'spam_reasons' => null,
            'status_lead' => StatusLead::New,
            'meeting_status' => null,
            'scheduled_at' => null,
            'previous_scheduled_at' => null,
            'follow_up_calls' => null,
            'notes' => null,
            'owner' => null,
        ];
    }

    /**
     * A company (B2B) lead.
     */
    public function company(): self
    {
        return $this->state(fn (): array => [
            'client_type' => ClientType::Company,
            'company_name' => $this->faker->company(),
        ]);
    }

    /**
     * A lead with a confirmed, future meeting.
     */
    public function confirmed(): self
    {
        return $this->state(fn (): array => [
            'meeting_status' => MeetingStatus::Confirmed,
            'scheduled_at' => now()->addWeekday()->setTime(10, 0),
        ]);
    }

    /**
     * A cancelled meeting.
     */
    public function cancelled(): self
    {
        return $this->state(fn (): array => [
            'meeting_status' => MeetingStatus::Cancelled,
            'scheduled_at' => now()->addWeekday()->setTime(10, 0),
        ]);
    }

    /**
     * Lead already reviewed by an operator.
     */
    public function read(): self
    {
        return $this->state(fn (): array => ['readed' => true]);
    }

    /**
     * A lead flagged by the content-scoring SpamGuard.
     */
    public function spam(): self
    {
        return $this->state(fn (): array => [
            'is_spam' => true,
            'spam_score' => 8,
            'spam_reasons' => ['keyword:casino', 'links:3'],
        ]);
    }
}
