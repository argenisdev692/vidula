<?php

declare(strict_types=1);

namespace Modules\Meeting\Domain\Exceptions;

/**
 * Thrown when a submitted attendee `{type, uuid}` pair does not resolve to a
 * real, active row — e.g. a stale uuid from a client that had the record open
 * before it was deleted. Caught by the controller and surfaced as a 422.
 */
final class AttendeeNotEligibleException extends \RuntimeException
{
    public static function forUuid(string $type, string $uuid): self
    {
        return new self("No eligible {$type} attendee found for uuid {$uuid}.");
    }
}
