<?php

declare(strict_types=1);

namespace Modules\ContactSupport\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\ContactSupport\Domain\Ports\ContactSupportRepositoryPort;
use Modules\ContactSupport\Domain\Services\SpamGuard;
use Modules\ContactSupport\Infrastructure\Persistence\Repositories\EloquentContactSupportRepository;

final class ContactSupportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ContactSupportRepositoryPort::class, EloquentContactSupportRepository::class);

        // Inject the config-driven weights into the framework-free SpamGuard so
        // the Domain service stays free of Laravel's config() helper.
        $this->app->singleton(SpamGuard::class, fn (): SpamGuard => new SpamGuard(
            keywordWeights: (array) config('contact_support.spam.keywords', []),
            threshold: (int) config('contact_support.spam.threshold', 5),
            maxLinks: (int) config('contact_support.spam.max_links', 1),
        ));
    }

    public function boot(): void
    {
        Route::middleware('web')->group(__DIR__.'/../Infrastructure/Routes/web.php');
        Route::middleware('api')->prefix('api')->group(__DIR__.'/../Infrastructure/Routes/api.php');
    }
}
