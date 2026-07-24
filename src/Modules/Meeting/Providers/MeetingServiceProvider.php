<?php

declare(strict_types=1);

namespace Modules\Meeting\Providers;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Appointment\Infrastructure\Persistence\Eloquent\Models\AppointmentEloquentModel;
use Modules\ContactSupport\Infrastructure\Persistence\Eloquent\Models\ContactSupportEloquentModel;
use Modules\Meeting\Application\Listeners\SendMeetingCancelledEmailListener;
use Modules\Meeting\Application\Listeners\SendMeetingInvitationEmailListener;
use Modules\Meeting\Application\Listeners\SendMeetingUpdatedEmailListener;
use Modules\Meeting\Application\Listeners\SyncMeetingCancelledToGoogleCalendarListener;
use Modules\Meeting\Application\Listeners\SyncMeetingCreatedToGoogleCalendarListener;
use Modules\Meeting\Application\Listeners\SyncMeetingUpdatedToGoogleCalendarListener;
use Modules\Meeting\Domain\Events\MeetingCancelled;
use Modules\Meeting\Domain\Events\MeetingScheduled;
use Modules\Meeting\Domain\Events\MeetingUpdated;
use Modules\Meeting\Domain\Ports\AppointmentCalendarFeedPort;
use Modules\Meeting\Domain\Ports\GoogleCalendarSyncPort;
use Modules\Meeting\Domain\Ports\MeetingRepositoryPort;
use Modules\Meeting\Infrastructure\Appointment\AppointmentCalendarFeedAdapter;
use Modules\Meeting\Infrastructure\GoogleCalendar\SpatieGoogleCalendarSyncAdapter;
use Modules\Meeting\Infrastructure\Persistence\Repositories\EloquentMeetingRepository;

final class MeetingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MeetingRepositoryPort::class, EloquentMeetingRepository::class);
        $this->app->bind(AppointmentCalendarFeedPort::class, AppointmentCalendarFeedAdapter::class);
        $this->app->bind(GoogleCalendarSyncPort::class, SpatieGoogleCalendarSyncAdapter::class);
    }

    public function boot(): void
    {
        Route::middleware('web')->group(__DIR__.'/../Infrastructure/Routes/web.php');

        // Explicit aliases decouple `meeting_attendees.attendable_type` from the
        // models' internal namespaces (see {@see AttendeeType}). Use morphMap()
        // — not enforceMorphMap() — so Spatie Permission / Activitylog models
        // that are not attendable types can still resolve their morph class.
        Relation::morphMap([
            'user' => User::class,
            'lead' => AppointmentEloquentModel::class,
            'contact' => ContactSupportEloquentModel::class,
        ]);

        // Registered explicitly because attribute auto-discovery only scans
        // app/Listeners (mirrors Appointment/Availability). Both the Google
        // Calendar push-sync and the attendee email run as separate queued
        // listeners on the same event — a Google API failure never blocks
        // the email, and vice versa.
        Event::listen(MeetingScheduled::class, [SyncMeetingCreatedToGoogleCalendarListener::class, 'handle']);
        Event::listen(MeetingScheduled::class, [SendMeetingInvitationEmailListener::class, 'handle']);
        Event::listen(MeetingUpdated::class, [SyncMeetingUpdatedToGoogleCalendarListener::class, 'handle']);
        Event::listen(MeetingUpdated::class, [SendMeetingUpdatedEmailListener::class, 'handle']);
        Event::listen(MeetingCancelled::class, [SyncMeetingCancelledToGoogleCalendarListener::class, 'handle']);
        Event::listen(MeetingCancelled::class, [SendMeetingCancelledEmailListener::class, 'handle']);
    }
}
