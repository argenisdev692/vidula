<?php

declare(strict_types=1);

namespace Modules\Meeting\Infrastructure\GoogleCalendar;

use Illuminate\Support\Facades\Log;
use Modules\Meeting\Domain\Ports\GoogleCalendarSyncPort;
use Modules\Meeting\Infrastructure\Attendees\AttendeeEmailResolver;
use Modules\Meeting\Infrastructure\Persistence\Eloquent\Models\MeetingEloquentModel;
use Spatie\GoogleCalendar\Event;

/**
 * Pushes a Meeting to the single shared calendar configured in
 * `config('google-calendar.calendar_id')` (the OAuth profile authenticates as
 * one Google account/company calendar — spatie/laravel-google-calendar's
 * OAuth2 mode is not natively multi-tenant per staff member, confirmed during
 * research; per-organizer personal calendars would need a custom multi-account
 * token store and are out of scope). Every method swallows and logs failures
 * (missing token, API outage, disabled config) rather than throwing — this
 * adapter is only ever invoked from a queued listener, so a failure here must
 * not retry-storm or surface to the user (spec.md NFR-Availability).
 */
final readonly class SpatieGoogleCalendarSyncAdapter implements GoogleCalendarSyncPort
{
    public function createEvent(MeetingEloquentModel $meeting): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $event = new Event;
            $this->fill($event, $meeting);
            $saved = $event->save();

            return $saved->id;
        } catch (\Throwable $e) {
            Log::warning('Failed to push meeting to Google Calendar', [
                'meeting_uuid' => $meeting->uuid,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function updateEvent(MeetingEloquentModel $meeting): void
    {
        if (! $this->isConfigured() || $meeting->google_event_id === null) {
            return;
        }

        try {
            $event = Event::find($meeting->google_event_id);
            $this->fill($event, $meeting);
            $event->save();
        } catch (\Throwable $e) {
            Log::warning('Failed to update the Google Calendar event for a meeting', [
                'meeting_uuid' => $meeting->uuid,
                'google_event_id' => $meeting->google_event_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function deleteEvent(MeetingEloquentModel $meeting): void
    {
        if (! $this->isConfigured() || $meeting->google_event_id === null) {
            return;
        }

        try {
            Event::find($meeting->google_event_id)->delete();
        } catch (\Throwable $e) {
            Log::warning('Failed to delete the Google Calendar event for a meeting', [
                'meeting_uuid' => $meeting->uuid,
                'google_event_id' => $meeting->google_event_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function fill(Event $event, MeetingEloquentModel $meeting): void
    {
        $event->name = $meeting->title;
        $event->description = $meeting->description;
        $event->startDateTime = $meeting->starts_at;
        $event->endDateTime = $meeting->ends_at;

        foreach (AttendeeEmailResolver::resolve($meeting->attendees) as $attendee) {
            if ($attendee['email'] === '' || $attendee['email'] === null) {
                continue;
            }
            $event->addAttendee(['email' => $attendee['email'], 'name' => $attendee['name']]);
        }
    }

    /**
     * Sync is opt-in: without a `calendar_id` configured (or a token file that
     * doesn't exist yet — e.g. the developer hasn't run the OAuth quickstart),
     * every call above becomes a silent no-op instead of a fatal error on
     * every meeting create/update.
     */
    private function isConfigured(): bool
    {
        return filled(config('google-calendar.calendar_id'));
    }
}
