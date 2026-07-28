<?php

declare(strict_types=1);

namespace Modules\Meeting\Application\Support;

use Illuminate\Validation\ValidationException;
use Modules\Meeting\Application\DTOs\MeetingAttendeeData;
use Modules\Meeting\Domain\Exceptions\AttendeeNotEligibleException;
use Modules\Meeting\Domain\Ports\AttendeeResolverPort;

/**
 * Shared attendee-resolution used by Create and Update handlers (DRY).
 * Converts `{type, uuid}` payloads into morph rows, or surfaces a 422 when
 * a uuid no longer resolves.
 */
final readonly class ResolveMeetingAttendees
{
    public function __construct(private AttendeeResolverPort $resolver) {}

    /**
     * @param  list<MeetingAttendeeData|array{type: string, uuid: string}>  $attendees
     * @return array<int, array{attendable_type: string, attendable_id: int}>
     */
    #[\NoDiscard]
    public function handle(array $attendees): array
    {
        try {
            $rows = [];

            foreach ($attendees as $attendee) {
                $dto = $attendee instanceof MeetingAttendeeData
                    ? $attendee
                    : MeetingAttendeeData::from($attendee);

                $rows[] = $this->resolver->resolve($dto->type, $dto->uuid);
            }

            return $rows;
        } catch (AttendeeNotEligibleException $e) {
            throw ValidationException::withMessages(['attendees' => [$e->getMessage()]]);
        }
    }
}
