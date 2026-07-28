<?php

declare(strict_types=1);

namespace Modules\Meeting\Domain\Exceptions;

use Modules\Meeting\Application\Support\ResolveMeetingAttendees;

/**
 * Thrown when a submitted attendee `{type, uuid}` pair does not resolve to a
 * real, active row — e.g. a stale uuid from a client that had the record open
 * before it was deleted. Caught by Create/Update handlers (via
 * {@see ResolveMeetingAttendees}) and
 * surfaced as a 422 ValidationException.
 */
final class AttendeeNotEligibleException extends \RuntimeException
{
    public static function forUuid(string $type, string $uuid): self
    {
        return new self("No eligible {$type} attendee found for uuid {$uuid}.");
    }
}
