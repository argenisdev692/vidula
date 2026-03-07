<?php

declare(strict_types=1);

namespace Modules\Permissions\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Permissions\Domain\Ports\PermissionRepositoryPort;
use Modules\Permissions\Infrastructure\Persistence\Repositories\EloquentPermissionRepository;

final class PermissionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PermissionRepositoryPort::class, EloquentPermissionRepository::class);
    }

    public function boot(): void
    {
        $this->registerWebRoutes();
        $this->registerApiRoutes();
    }

    private function registerWebRoutes(): void
    {
        Route::middleware(['web', 'auth'])
            ->prefix('permissions')
            ->group(__DIR__ . '/../Infrastructure/Routes/web.php');
    }

    private function registerApiRoutes(): void
    {
        Route::middleware(['api'])
            ->prefix('api/permissions')
            ->group(__DIR__ . '/../Infrastructure/Routes/api.php');
    }
}
