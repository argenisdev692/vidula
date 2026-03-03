<?php

declare(strict_types=1);

namespace Modules\Students\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Students\Domain\Ports\StudentRepositoryPort;
use Modules\Students\Infrastructure\Persistence\Repositories\EloquentStudentRepository;

/**
 * StudentServiceProvider
 */
final class StudentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(StudentRepositoryPort::class, EloquentStudentRepository::class);
    }

    public function boot(): void
    {
        $this->registerRoutes();
    }

    private function registerRoutes(): void
    {
        // Web Routes
        Route::middleware(['web', 'auth'])
            ->prefix('students')
            ->group(__DIR__ . '/../Infrastructure/Routes/web.php');

        // Api Routes
        Route::middleware(['api', 'auth:sanctum'])
            ->prefix('api/students')
            ->group(__DIR__ . '/../Infrastructure/Routes/api.php');
    }
}
