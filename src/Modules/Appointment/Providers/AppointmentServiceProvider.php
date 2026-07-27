<?php

declare(strict_types=1);

namespace Modules\Appointment\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Appointment\Application\Listeners\SendAppointmentCancelledEmailListener;
use Modules\Appointment\Application\Listeners\SendAppointmentConfirmedEmailListener;
use Modules\Appointment\Application\Listeners\SendAppointmentReceivedEmailListener;
use Modules\Appointment\Application\Listeners\SendAppointmentRescheduledEmailListener;
use Modules\Appointment\Application\Listeners\SendNewLeadAdminEmailListener;
use Modules\Appointment\Domain\Events\AppointmentBooked;
use Modules\Appointment\Domain\Events\AppointmentCancelled;
use Modules\Appointment\Domain\Events\AppointmentConfirmed;
use Modules\Appointment\Domain\Events\AppointmentRescheduled;
use Modules\Appointment\Domain\Ports\AppointmentRepositoryPort;
use Modules\Appointment\Domain\Ports\AvailabilityPort;
use Modules\Appointment\Domain\Services\SpamGuard;
use Modules\Appointment\Infrastructure\Availability\AvailabilityResolverAdapter;
use Modules\Appointment\Infrastructure\Listeners\BroadcastAppointmentSubmittedListener;
use Modules\Appointment\Infrastructure\Persistence\Repositories\EloquentAppointmentRepository;

final class AppointmentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AppointmentRepositoryPort::class, EloquentAppointmentRepository::class);
        $this->app->bind(AvailabilityPort::class, AvailabilityResolverAdapter::class);

        // Inject the config-driven weights into the framework-free SpamGuard so
        // the Domain service stays free of Laravel's config() helper.
        $this->app->singleton(SpamGuard::class, fn (): SpamGuard => new SpamGuard(
            keywordWeights: (array) config('appointment.spam.keywords', []),
            threshold: (int) config('appointment.spam.threshold', 5),
            maxLinks: (int) config('appointment.spam.max_links', 1),
        ));
    }

    public function boot(): void
    {
        Route::middleware('web')->group(__DIR__.'/../Infrastructure/Routes/web.php');
        Route::middleware('api')->prefix('api')->group(__DIR__.'/../Infrastructure/Routes/api.php');

        // Registered explicitly because attribute auto-discovery only scans
        // app/Listeners (mirrors Availability's ResyncHolidaysOnCountryChange).
        // A new booking notifies BOTH the company inbox and the client.
        Event::listen(AppointmentBooked::class, [SendNewLeadAdminEmailListener::class, 'handle']);
        Event::listen(AppointmentBooked::class, [SendAppointmentReceivedEmailListener::class, 'handle']);
        Event::listen(AppointmentBooked::class, [BroadcastAppointmentSubmittedListener::class, 'handle']);
        Event::listen(AppointmentConfirmed::class, [SendAppointmentConfirmedEmailListener::class, 'handle']);
        Event::listen(AppointmentRescheduled::class, [SendAppointmentRescheduledEmailListener::class, 'handle']);
        Event::listen(AppointmentCancelled::class, [SendAppointmentCancelledEmailListener::class, 'handle']);
    }
}
