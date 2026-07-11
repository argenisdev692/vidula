<?php

declare(strict_types=1);

namespace Modules\Portfolio\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Portfolio\Domain\Ports\PortfolioRepositoryPort;
use Modules\Portfolio\Infrastructure\Persistence\Repositories\EloquentPortfolioRepository;

final class PortfolioServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PortfolioRepositoryPort::class, EloquentPortfolioRepository::class);
    }

    public function boot(): void
    {
        Route::middleware('web')->group(__DIR__.'/../Infrastructure/Routes/web.php');
        Route::middleware('api')->prefix('api')->group(__DIR__.'/../Infrastructure/Routes/api.php');
    }
}
