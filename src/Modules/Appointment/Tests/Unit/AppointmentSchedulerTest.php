<?php

declare(strict_types=1);

namespace Modules\Appointment\Tests\Unit;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Appointment\Domain\Exceptions\PastDateSchedulingException;
use Modules\Appointment\Domain\Exceptions\SlotUnavailableException;
use Modules\Appointment\Domain\Services\AppointmentScheduler;
use Modules\Appointment\Infrastructure\Persistence\Eloquent\Models\AppointmentEloquentModel;
use Modules\Availability\Infrastructure\Persistence\Eloquent\Models\AvailabilityRuleEloquentModel;
use Tests\TestCase;

final class AppointmentSchedulerTest extends TestCase
{
    use RefreshDatabase;

    private AppointmentScheduler $scheduler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scheduler = app(AppointmentScheduler::class);
    }

    public function test_a_past_date_is_rejected(): void
    {
        $this->expectException(PastDateSchedulingException::class);

        $this->scheduler->assertBookable(CarbonImmutable::parse('2020-01-01 10:00'));
    }

    public function test_a_day_with_no_availability_rule_is_rejected(): void
    {
        $this->expectException(SlotUnavailableException::class);

        // 2026-12-13 is a Sunday with no rule seeded.
        $this->scheduler->assertBookable(CarbonImmutable::parse('2026-12-13 10:00'));
    }

    public function test_a_time_outside_every_open_slot_is_rejected(): void
    {
        AvailabilityRuleEloquentModel::factory()->forDay(5)->slot('09:00', '13:00')->create();

        $this->expectException(SlotUnavailableException::class);

        // 2026-12-11 is a Friday; 15:00 falls outside the 09:00-13:00 window.
        $this->scheduler->assertBookable(CarbonImmutable::parse('2026-12-11 15:00'));
    }

    public function test_a_future_time_inside_an_open_slot_is_accepted(): void
    {
        AvailabilityRuleEloquentModel::factory()->forDay(5)->slot('09:00', '13:00')->create();

        $this->scheduler->assertBookable(CarbonImmutable::parse('2026-12-11 10:00'));

        $this->addToAssertionCount(1);
    }

    public function test_a_timestamp_already_booked_by_another_appointment_is_rejected(): void
    {
        AvailabilityRuleEloquentModel::factory()->forDay(5)->slot('09:00', '13:00')->create();
        AppointmentEloquentModel::factory()->confirmed()->create([
            'scheduled_at' => CarbonImmutable::parse('2026-12-11 10:00'),
        ]);

        $this->expectException(SlotUnavailableException::class);

        $this->scheduler->assertBookable(CarbonImmutable::parse('2026-12-11 10:00'));
    }

    public function test_excluding_the_appointments_own_uuid_avoids_a_false_conflict(): void
    {
        AvailabilityRuleEloquentModel::factory()->forDay(5)->slot('09:00', '13:00')->create();
        $appointment = AppointmentEloquentModel::factory()->confirmed()->create([
            'scheduled_at' => CarbonImmutable::parse('2026-12-11 10:00'),
        ]);

        $this->scheduler->assertBookable(CarbonImmutable::parse('2026-12-11 10:00'), $appointment->uuid);

        $this->addToAssertionCount(1);
    }
}
