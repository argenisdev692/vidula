<?php

declare(strict_types=1);

namespace Modules\Meeting\Domain\Ports;

use Modules\Meeting\Application\DTOs\GoogleCalendarEventSyncResult;
use Modules\Meeting\Infrastructure\Persistence\Eloquent\Models\MeetingEloquentModel;

/**
 * Push-sync boundary onto Google Calendar (`spatie/laravel-google-calendar`).
 * Every method degrades gracefully (returns/no-ops on failure instead of
 * throwing) — a Google API outage or missing OAuth token must NEVER block
 * meeting CRUD (spec.md NFR-Availability).
 */
interface GoogleCalendarSyncPort
{
    /**
     * Creates the Google Calendar event and returns its sync result, or null
     * if sync is disabled/not configured or the push failed.
     */
    public function createEvent(MeetingEloquentModel $meeting): ?GoogleCalendarEventSyncResult;

    /**
     * No-ops if the meeting has no `google_event_id` yet (sync was never
     * established) or the push fails.
     */
    public function updateEvent(MeetingEloquentModel $meeting): void;

    /**
     * No-ops if the meeting has no `google_event_id` or the push fails.
     */
    public function deleteEvent(MeetingEloquentModel $meeting): void;
}
