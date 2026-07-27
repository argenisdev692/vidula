<?php

declare(strict_types=1);

namespace Modules\Meeting\Domain\Ports;

use Modules\Meeting\Domain\Exceptions\AttendeeNotEligibleException;

/**
 * Resolves a client-submitted `{type, uuid}` pair to the morph owner key used
 * by `meeting_attendees.attendable_*`. Lives as a port so Application handlers
 * never import Infrastructure Eloquent resolvers (DIP).
 */
interface AttendeeResolverPort
{
    /**
     * @return array{attendable_type: string, attendable_id: int}
     *
     * @throws AttendeeNotEligibleException
     */
    public function resolve(string $type, string $uuid): array;
}
