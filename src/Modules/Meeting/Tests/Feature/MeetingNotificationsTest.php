<?php

declare(strict_types=1);

namespace Modules\Meeting\Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Modules\Meeting\Application\DTOs\GoogleCalendarEventSyncResult;
use Modules\Meeting\Domain\Ports\GoogleCalendarSyncPort;
use Modules\Meeting\Infrastructure\Mail\MeetingCancelledMail;
use Modules\Meeting\Infrastructure\Mail\MeetingInvitationMail;
use Modules\Meeting\Infrastructure\Persistence\Eloquent\Models\MeetingEloquentModel;
use Tests\TestCase;

final class MeetingNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function superAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('SUPER_ADMIN');

        return $admin;
    }

    /**
     * A fake port lets the test assert push-sync was attempted without ever
     * calling the real Google API — `QUEUE_CONNECTION=sync` in phpunit.xml
     * means the queued listener runs inline, so this is captured synchronously.
     */
    private function fakeGoogleCalendar(): object
    {
        return new class implements GoogleCalendarSyncPort
        {
            public array $created = [];

            public array $updated = [];

            public array $deleted = [];

            public function createEvent(MeetingEloquentModel $meeting): ?GoogleCalendarEventSyncResult
            {
                $this->created[] = $meeting->uuid;

                return new GoogleCalendarEventSyncResult(
                    eventId: 'fake-google-event-id',
                    meetLink: 'https://meet.google.com/fake-test-link',
                );
            }

            public function updateEvent(MeetingEloquentModel $meeting): void
            {
                $this->updated[] = $meeting->uuid;
            }

            public function deleteEvent(MeetingEloquentModel $meeting): void
            {
                $this->deleted[] = $meeting->uuid;
            }
        };
    }

    public function test_creating_a_meeting_emails_every_attendee_and_pushes_to_google_calendar(): void
    {
        Mail::fake();
        $fakeCalendar = $this->fakeGoogleCalendar();
        $this->app->instance(GoogleCalendarSyncPort::class, $fakeCalendar);

        $attendeeUser = User::factory()->create(['first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.com']);

        $this->actingAs($this->superAdmin())->post('/meetings', [
            'title' => 'Kickoff call',
            'description' => null,
            'starts_at' => '2026-12-11 10:00:00',
            'attendees' => [['type' => 'user', 'uuid' => $attendeeUser->uuid]],
        ])->assertRedirect();

        Mail::assertSent(
            MeetingInvitationMail::class,
            fn (MeetingInvitationMail $mail): bool => $mail->hasTo('ada@example.com'),
        );

        $meeting = MeetingEloquentModel::query()->where('title', 'Kickoff call')->firstOrFail();
        $this->assertContains($meeting->uuid, $fakeCalendar->created);
        $this->assertSame('fake-google-event-id', $meeting->google_event_id);
        $this->assertSame('https://meet.google.com/fake-test-link', $meeting->meet_link);
    }

    public function test_cancelling_a_meeting_emails_attendees_and_deletes_the_google_calendar_event(): void
    {
        Mail::fake();
        $fakeCalendar = $this->fakeGoogleCalendar();
        $this->app->instance(GoogleCalendarSyncPort::class, $fakeCalendar);

        $attendeeUser = User::factory()->create(['email' => 'attendee@example.com']);
        $meeting = MeetingEloquentModel::factory()->create(['google_event_id' => 'existing-google-event-id']);
        $meeting->attendees()->create(['attendable_type' => 'user', 'attendable_id' => $attendeeUser->id]);

        $this->actingAs($this->superAdmin())
            ->patch("/meetings/{$meeting->uuid}/cancel")
            ->assertRedirect();

        Mail::assertSent(
            MeetingCancelledMail::class,
            fn (MeetingCancelledMail $mail): bool => $mail->hasTo('attendee@example.com'),
        );
        $this->assertContains($meeting->uuid, $fakeCalendar->deleted);
    }

    /**
     * Exercises the REAL `SpatieGoogleCalendarSyncAdapter` (not a fake) — the
     * test environment has no `GOOGLE_CALENDAR_ID` configured, the same state
     * as any developer machine before the OAuth quickstart is run. This must
     * be a silent no-op, never a fatal error blocking meeting creation
     * (spec.md NFR-Availability).
     *
     * A Google API exception AFTER the adapter's `isConfigured()` gate is
     * already swallowed by its own try/catch (see the class docblock) — not
     * re-verified here, since `QUEUE_CONNECTION=sync` in phpunit.xml runs
     * queued listeners inline, and only the real adapter's own try/catch (not
     * a substituted fake) reflects how a queued listener actually behaves in
     * production, where a worker-process failure never touches the HTTP
     * response in the first place.
     */
    public function test_meeting_creation_succeeds_when_google_calendar_is_not_configured(): void
    {
        Mail::fake();
        config(['google-calendar.calendar_id' => null]);

        $this->actingAs($this->superAdmin())->post('/meetings', [
            'title' => 'Resilience check',
            'description' => null,
            'starts_at' => '2026-12-11 10:00:00',
            'attendees' => [],
        ])->assertRedirect();

        $meeting = MeetingEloquentModel::query()->where('title', 'Resilience check')->firstOrFail();
        $this->assertNull($meeting->google_event_id);
    }
}
