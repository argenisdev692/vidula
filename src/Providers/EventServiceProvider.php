<?php

declare(strict_types=1);

namespace Src\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Auth\Domain\Events\OtpGenerated;
use Modules\Auth\Domain\Events\UserLoggedIn;
use Modules\Auth\Infrastructure\Listeners\RecordAuthActivityListener;

final class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        OtpGenerated::class => [
            RecordAuthActivityListener::class,
        ],
        UserLoggedIn::class => [
            RecordAuthActivityListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();
    }
}
