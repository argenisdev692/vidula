<?php

declare(strict_types=1);

namespace Modules\ContactSupport\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\ContactSupport\Domain\Ports\ContactSupportRepositoryPort;
use Modules\ContactSupport\Infrastructure\Persistence\Repositories\EloquentContactSupportRepository;

final class ContactSupportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ContactSupportRepositoryPort::class, EloquentContactSupportRepository::class);
    }

    public function boot(): void
    {
        Route::middleware('web')->group(__DIR__.'/../Infrastructure/Routes/web.php');
        Route::middleware('api')->prefix('api')->group(__DIR__.'/../Infrastructure/Routes/api.php');
    }
}
