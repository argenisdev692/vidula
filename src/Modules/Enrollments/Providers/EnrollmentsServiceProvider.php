<?php

declare(strict_types=1);

namespace Modules\Enrollments\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Enrollments\Domain\Ports\EnrollmentRepositoryPort;
use Modules\Enrollments\Infrastructure\Persistence\Repositories\EloquentEnrollmentRepository;

final class EnrollmentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(EnrollmentRepositoryPort::class, EloquentEnrollmentRepository::class);
    }

    public function boot(): void
    {
        Route::middleware('web')->group(__DIR__.'/../Infrastructure/Routes/web.php');
        Route::middleware('api')->prefix('api')->group(__DIR__.'/../Infrastructure/Routes/api.php');
    }
}
