<?php

declare(strict_types=1);

namespace Modules\Meeting\Domain\Events;

/**
 * Raised after any successful update (details, time range, or attendee list).
 */
final readonly class MeetingUpdated
{
    public function __construct(public string $uuid) {}
}
