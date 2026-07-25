<?php

declare(strict_types=1);

namespace Modules\Cvs\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Cvs\Domain\Ports\CvRepositoryPort;
use Modules\Cvs\Infrastructure\Persistence\Repositories\EloquentCvRepository;

final class CvsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CvRepositoryPort::class, EloquentCvRepository::class);
    }

    public function boot(): void
    {
        Route::middleware('web')->group(__DIR__.'/../Infrastructure/Routes/web.php');
        Route::middleware('api')->prefix('api')->group(__DIR__.'/../Infrastructure/Routes/api.php');
    }
}
