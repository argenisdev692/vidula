<?php

declare(strict_types=1);

namespace Modules\Users\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Users\Domain\Ports\UserRepositoryPort;
use Modules\Users\Domain\Ports\UserProfileRepositoryPort;
use Modules\Users\Infrastructure\Persistence\Repositories\EloquentUserRepository;
use Modules\Users\Infrastructure\Persistence\Repositories\EloquentUserProfileRepository;

/**
 * UsersServiceProvider — Binds Users context ports to their infrastructure adapters.
 */
final class UsersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ── Domain Ports → Infrastructure Adapters ──
        $this->app->bind(UserRepositoryPort::class, EloquentUserRepository::class);
        $this->app->bind(UserProfileRepositoryPort::class, EloquentUserProfileRepository::class);
        $this->app->bind(\Modules\Users\Domain\Ports\StoragePort::class, \Modules\Users\Infrastructure\ExternalServices\Storage\AvatarStorageAdapter::class);
    }

    public function boot(): void
    {
        // ── Context-specific Resources ──
        $this->loadMigrationsFrom(__DIR__ . '/../Infrastructure/Persistence/Eloquent/Migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Modules\Users\Infrastructure\CLI\ResendSetupEmailCommand::class,
            ]);
        }

        // ── Context-specific Route Loading ──
        $this->registerWebRoutes();
        $this->registerApiRoutes();
    }

    private function registerWebRoutes(): void
    {
        Route::middleware(['web', 'auth'])
            ->prefix('users')
            ->group(__DIR__ . '/../Infrastructure/Routes/web.php');
    }

    private function registerApiRoutes(): void
    {
        Route::middleware(['api'])
            ->prefix('api/users')
            ->group(__DIR__ . '/../Infrastructure/Routes/api.php');
    }
}
