<?php

declare(strict_types=1);

namespace Modules\Meeting\Domain\ValueObjects;

/**
 * Lifecycle of an internal Meeting. No recurrence, no "Completed" state — a
 * past `ends_at` on a `Scheduled` meeting is derived at read time, not stored
 * (spec.md clarify.md Q5 — single occurrence only).
 */
enum MeetingStatus: string
{
    case Scheduled = 'Scheduled';

    case Cancelled = 'Cancelled';
}
