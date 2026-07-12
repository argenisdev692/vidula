<?php

declare(strict_types=1);

namespace Modules\Appointment\Domain\ValueObjects;

/**
 * Lifecycle of a booked meeting. Null (no case) means "requested, not yet
 * confirmed" — the three states below are only reached through the dedicated
 * Confirm / Reschedule / Cancel command handlers, never a generic update.
 */
enum MeetingStatus: string
{
    case Confirmed = 'Confirmed';

    case Rescheduled = 'Rescheduled';

    case Cancelled = 'Cancelled';
}
