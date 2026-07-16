<?php

declare(strict_types=1);

namespace Modules\Meeting\Application\Commands;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Meeting\Application\DTOs\CreateMeetingData;
use Modules\Meeting\Domain\Events\MeetingScheduled;
use Modules\Meeting\Domain\Exceptions\AttendeeNotEligibleException;
use Modules\Meeting\Domain\Ports\MeetingRepositoryPort;
use Modules\Meeting\Domain\ValueObjects\MeetingStatus;
use Modules\Meeting\Infrastructure\Attendees\AttendeeResolver;
use Modules\Meeting\Infrastructure\Persistence\Eloquent\Models\MeetingEloquentModel;

/**
 * `organizer_id` is set here from the authenticated user, never accepted from
 * the request payload (OWASP API3 — research.md §5).
 */
final readonly class CreateMeetingHandler
{
    public function __construct(
        private MeetingRepositoryPort $meetings,
        private AttendeeResolver $resolver,
        private Dispatcher $events,
    ) {}

    #[\NoDiscard]
    public function handle(CreateMeetingData $data, int $organizerId): MeetingEloquentModel
    {
        $meeting = DB::transaction(function () use ($data, $organizerId): MeetingEloquentModel {
            $meeting = $this->meetings->create([
                'organizer_id' => $organizerId,
                'title' => $data->title,
                'description' => $data->description,
                'starts_at' => $data->startsAt,
                'ends_at' => $data->endsAt,
                'status' => MeetingStatus::Scheduled,
            ]);

            $this->meetings->syncAttendees($meeting, $this->resolveAttendees($data));

            return $meeting->refresh();
        });

        // Queued listeners push to Google Calendar and email attendees — see
        // SyncMeetingCreatedToGoogleCalendarListener / SendMeetingInvitationEmailListener.
        $this->events->dispatch(new MeetingScheduled($meeting->uuid));

        return $meeting;
    }

    /**
     * @return array<int, array{attendable_type: string, attendable_id: int}>
     */
    private function resolveAttendees(CreateMeetingData $data): array
    {
        try {
            return $data->attendees
                ->map(fn ($attendee) => $this->resolver->resolve($attendee->type, $attendee->uuid))
                ->all();
        } catch (AttendeeNotEligibleException $e) {
            throw ValidationException::withMessages(['attendees' => [$e->getMessage()]]);
        }
    }
}
