<?php

declare(strict_types=1);

namespace Modules\Meeting\Application\Commands;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Modules\Meeting\Domain\Events\MeetingCancelled;
use Modules\Meeting\Domain\Ports\MeetingRepositoryPort;
use Modules\Meeting\Domain\ValueObjects\MeetingStatus;
use Modules\Meeting\Infrastructure\Persistence\Eloquent\Models\MeetingEloquentModel;

final readonly class CancelMeetingHandler
{
    public function __construct(
        private MeetingRepositoryPort $meetings,
        private Cache $cache,
        private Dispatcher $events,
    ) {}

    #[\NoDiscard]
    public function handle(MeetingEloquentModel $meeting): MeetingEloquentModel
    {
        $updated = DB::transaction(
            fn () => $this->meetings->update($meeting, ['status' => MeetingStatus::Cancelled]),
        );

        $this->cache->forget("meeting_{$meeting->uuid}");

        // Queued listeners delete the Google Calendar event and email
        // attendees — see SyncMeetingCancelledToGoogleCalendarListener /
        // SendMeetingCancelledEmailListener.
        $this->events->dispatch(new MeetingCancelled($updated->uuid));

        return $updated;
    }
}
