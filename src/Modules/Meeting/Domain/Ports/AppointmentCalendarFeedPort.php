<?php

declare(strict_types=1);

namespace Modules\Meeting\Domain\Ports;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Modules\Meeting\Application\DTOs\CalendarEventData;

/**
 * Anti-corruption boundary onto the Appointment bounded context — mirrors the
 * existing `Modules\Appointment\Domain\Ports\AvailabilityPort` precedent
 * (Appointment reading Availability). Meeting depends on THIS abstraction
 * (owned here); the adapter in `Infrastructure/Appointment` is the only place
 * the two modules' scheduling internals meet. Read-only: Meeting never
 * mutates an Appointment record.
 */
interface AppointmentCalendarFeedPort
{
    /**
     * @return Collection<int, CalendarEventData>
     */
    public function between(CarbonInterface $from, CarbonInterface $to): Collection;
}
