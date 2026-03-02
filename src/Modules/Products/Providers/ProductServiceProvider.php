<?php

declare(strict_types=1);

namespace Modules\Products\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Products\Domain\Ports\ProductRepositoryPort;
use Modules\Products\Infrastructure\Persistence\Repositories\EloquentProductRepository;

/**
 * ProductServiceProvider
 */
final class ProductServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProductRepositoryPort::class, EloquentProductRepository::class);
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
            ->prefix('products')
            ->group(__DIR__ . '/../Infrastructure/Routes/web.php');

        // Api Routes
        Route::middleware(['api', 'auth:sanctum'])
            ->prefix('api/products')
            ->group(__DIR__ . '/../Infrastructure/Routes/api.php');
    }
}
