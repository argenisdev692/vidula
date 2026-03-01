<?php

declare(strict_types=1);

namespace Modules\CompanyData\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\CompanyData\Domain\Ports\CompanyDataRepositoryPort;
use Modules\CompanyData\Infrastructure\Persistence\Repositories\EloquentCompanyDataRepository;

/**
 * CompanyDataServiceProvider
 */
final class CompanyDataServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CompanyDataRepositoryPort::class, EloquentCompanyDataRepository::class);
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
            ->prefix('company-data')
            ->group(__DIR__ . '/../Infrastructure/Routes/web.php');

        // Api Routes
        Route::middleware(['api', 'auth:sanctum'])
            ->prefix('api/company-data')
            ->group(__DIR__ . '/../Infrastructure/Routes/api.php');
    }
}
