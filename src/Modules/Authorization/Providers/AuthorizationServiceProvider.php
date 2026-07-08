<?php

declare(strict_types=1);

namespace Modules\Authorization\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Authorization\Domain\Ports\PermissionRepositoryPort;
use Modules\Authorization\Domain\Ports\RoleRepositoryPort;
use Modules\Authorization\Infrastructure\Persistence\Repositories\EloquentPermissionRepository;
use Modules\Authorization\Infrastructure\Persistence\Repositories\EloquentRoleRepository;

final class AuthorizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RoleRepositoryPort::class, EloquentRoleRepository::class);
        $this->app->bind(PermissionRepositoryPort::class, EloquentPermissionRepository::class);
    }

    public function boot(): void
    {
        Route::middleware('web')->group(__DIR__.'/../Infrastructure/Routes/web.php');
        Route::middleware('api')->prefix('api')->group(__DIR__.'/../Infrastructure/Routes/api.php');
    }
}
