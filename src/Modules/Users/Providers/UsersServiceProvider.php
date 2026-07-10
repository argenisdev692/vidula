<?php

declare(strict_types=1);

namespace Modules\Users\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Users\Domain\Ports\InvitationLinkPort;
use Modules\Users\Domain\Ports\UserRepositoryPort;
use Modules\Users\Infrastructure\Invitation\SignedUrlInvitationAdapter;
use Modules\Users\Infrastructure\Persistence\Repositories\EloquentUserRepository;

final class UsersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryPort::class, EloquentUserRepository::class);
        $this->app->bind(InvitationLinkPort::class, SignedUrlInvitationAdapter::class);
    }

    public function boot(): void
    {
        $this->registerWebRoutes();
        $this->registerApiRoutes();
    }

    private function registerWebRoutes(): void
    {
        Route::middleware('web')->group(__DIR__.'/../Infrastructure/Routes/web.php');
    }

    private function registerApiRoutes(): void
    {
        Route::middleware('api')->group(__DIR__.'/../Infrastructure/Routes/api.php');
    }
}
