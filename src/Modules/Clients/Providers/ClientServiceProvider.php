<?php

declare(strict_types=1);

namespace Modules\Clients\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Clients\Domain\Ports\ClientRepositoryPort;
use Modules\Clients\Infrastructure\Persistence\Repositories\EloquentClientRepository;

/**
 * ClientServiceProvider
 */
final class ClientServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ClientRepositoryPort::class, EloquentClientRepository::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Infrastructure/Persistence/Eloquent/Migrations');
        $this->registerRoutes();
    }

    private function registerRoutes(): void
    {
        // Web Routes
        Route::middleware(['web', 'auth'])
            ->prefix('clients')
            ->group(__DIR__ . '/../Infrastructure/Routes/web.php');

        // Api Routes
        Route::middleware(['api', 'auth:sanctum'])
            ->prefix('api/clients')
            ->group(__DIR__ . '/../Infrastructure/Routes/api.php');
    }
}
