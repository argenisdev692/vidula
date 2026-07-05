<?php

declare(strict_types=1);

namespace Modules\Company\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Company\Domain\Ports\CompanyRepositoryPort;
use Modules\Company\Infrastructure\Persistence\Repositories\EloquentCompanyRepository;

final class CompanyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CompanyRepositoryPort::class, EloquentCompanyRepository::class);
    }

    public function boot(): void
    {
        $this->registerWebRoutes();
    }

    private function registerWebRoutes(): void
    {
        Route::middleware('web')->group(__DIR__.'/../Infrastructure/Routes/web.php');
    }
}
