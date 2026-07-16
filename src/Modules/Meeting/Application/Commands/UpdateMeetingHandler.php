<?php

declare(strict_types=1);

namespace Modules\Meeting\Application\Commands;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Meeting\Application\DTOs\UpdateMeetingData;
use Modules\Meeting\Domain\Events\MeetingUpdated;
use Modules\Meeting\Domain\Exceptions\AttendeeNotEligibleException;
use Modules\Meeting\Domain\Ports\MeetingRepositoryPort;
use Modules\Meeting\Infrastructure\Attendees\AttendeeResolver;
use Modules\Meeting\Infrastructure\Persistence\Eloquent\Models\MeetingEloquentModel;

/**
 * `organizer_id` is immutable after creation — never accepted here either.
 * Replaces the full attendee set (simplest correct semantics for a
 * resend-the-whole-list form).
 */
final readonly class UpdateMeetingHandler
{
    public function __construct(
        private MeetingRepositoryPort $meetings,
        private AttendeeResolver $resolver,
        private Cache $cache,
        private Dispatcher $events,
    ) {}

    #[\NoDiscard]
    public function handle(MeetingEloquentModel $meeting, UpdateMeetingData $data): MeetingEloquentModel
    {
        $updated = DB::transaction(function () use ($meeting, $data): MeetingEloquentModel {
            $meeting = $this->meetings->update($meeting, [
                'title' => $data->title,
                'description' => $data->description,
                'starts_at' => $data->startsAt,
                'ends_at' => $data->endsAt,
            ]);

            try {
                $rows = $data->attendees
                    ->map(fn ($attendee) => $this->resolver->resolve($attendee->type, $attendee->uuid))
                    ->all();
            } catch (AttendeeNotEligibleException $e) {
                throw ValidationException::withMessages(['attendees' => [$e->getMessage()]]);
            }

            $this->meetings->syncAttendees($meeting, $rows);

            return $meeting->refresh();
        });

        $this->cache->forget("meeting_{$meeting->uuid}");

        // Queued listeners push the change to Google Calendar and email
        // attendees — see SyncMeetingUpdatedToGoogleCalendarListener /
        // SendMeetingUpdatedEmailListener.
        $this->events->dispatch(new MeetingUpdated($updated->uuid));

        return $updated;
    }
}
