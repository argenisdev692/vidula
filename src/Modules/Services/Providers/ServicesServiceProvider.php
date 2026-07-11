<?php

declare(strict_types=1);

namespace Modules\Services\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Services\Domain\Ports\ServiceRepositoryPort;
use Modules\Services\Infrastructure\Persistence\Repositories\EloquentServiceRepository;

final class ServicesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ServiceRepositoryPort::class, EloquentServiceRepository::class);
    }

    public function boot(): void
    {
        Route::middleware('web')->group(__DIR__.'/../Infrastructure/Routes/web.php');
        Route::middleware('api')->prefix('api')->group(__DIR__.'/../Infrastructure/Routes/api.php');
    }
}
