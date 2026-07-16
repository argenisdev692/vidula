<?php

declare(strict_types=1);

namespace Modules\Meeting\Domain\Events;

/**
 * Raised once, right after a meeting is created — mirrors
 * `Modules\Appointment\Domain\Events\AppointmentBooked`. Carries only the
 * uuid; listeners re-fetch the current row (never a stale in-memory copy).
 */
final readonly class MeetingScheduled
{
    public function __construct(public string $uuid) {}
}
