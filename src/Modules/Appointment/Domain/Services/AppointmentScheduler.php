<?php

declare(strict_types=1);

namespace Modules\Appointment\Domain\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Modules\Appointment\Domain\Exceptions\PastDateSchedulingException;
use Modules\Appointment\Domain\Exceptions\SlotUnavailableException;
use Modules\Appointment\Domain\Ports\AppointmentRepositoryPort;
use Modules\Appointment\Domain\Ports\AvailabilityPort;
use Modules\Availability\Domain\ValueObjects\TimeSlot;

/**
 * Guards every appointment scheduling decision (first booking, confirm,
 * reschedule) behind one invariant chain: the date must not be in the past, the
 * day must be open per the {@see AvailabilityPort} (weekly rule + date
 * exceptions + holidays), the requested time must fall inside one of that day's
 * open windows, and the exact timestamp must not already be taken.
 *
 * Slot resolution is reused from `Availability` through the {@see AvailabilityPort}
 * anti-corruption boundary (bound to that module's resolver in Infrastructure)
 * rather than depending on the concrete service — {@see TimeSlot}'s own docblock
 * documents this as the intended integration point ("the appointment engine
 * slices these into bookable slots"), and the two Domain layers are treated as
 * one cohesive scheduling bounded context.
 */
final readonly class AppointmentScheduler
{
    public function __construct(
        private AvailabilityPort $availability,
        private AppointmentRepositoryPort $appointments,
    ) {}

    /**
     * @throws PastDateSchedulingException
     * @throws SlotUnavailableException
     */
    public function assertBookable(CarbonInterface $scheduledAt, ?string $excludeUuid = null): void
    {
        $today = CarbonImmutable::now()->startOfDay();

        if (CarbonImmutable::parse($scheduledAt)->startOfDay()->lessThan($today)) {
            throw new PastDateSchedulingException('Appointments cannot be scheduled on a past date.');
        }

        $day = $this->availability->resolve($scheduledAt);

        if (! $day->isOpen || ! $this->fitsWithinAnySlot($scheduledAt, $day->slots)) {
            throw new SlotUnavailableException('The requested date and time is not within an open availability window.');
        }

        if ($this->appointments->hasConflict($scheduledAt, $excludeUuid)) {
            throw new SlotUnavailableException('The requested date and time is already booked.');
        }
    }

    /**
     * @param  list<TimeSlot>  $slots
     */
    private function fitsWithinAnySlot(CarbonInterface $scheduledAt, array $slots): bool
    {
        $time = $scheduledAt->format('H:i');

        foreach ($slots as $slot) {
            if ($time >= $slot->start && $time < $slot->end) {
                return true;
            }
        }

        return false;
    }
}
