<?php

declare(strict_types=1);

namespace Src\Providers;

use Illuminate\Support\ServiceProvider;
use Shared\Infrastructure\Observability\HealthCheck\DatabaseHealthCheck;
use Shared\Infrastructure\Observability\HealthCheck\HealthCheckAggregator;
use Shared\Infrastructure\Resilience\CircuitBreaker\CircuitBreakerInterface;
use Shared\Infrastructure\Resilience\CircuitBreaker\RedisCircuitBreaker;
use Shared\Infrastructure\Persistence\Transactions\TransactionInterface;
use Shared\Infrastructure\Persistence\Transactions\DatabaseTransaction;
use Shared\Infrastructure\Audit\AuditInterface;
use Shared\Infrastructure\Audit\SpatieAuditAdapter;
use Shared\Infrastructure\Resilience\RateLimiter\CustomRateLimiter;
use Shared\Infrastructure\Export\ExportInterface;
use Shared\Infrastructure\Export\LaravelExportAdapter;

final class CoreServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->register(BusServiceProvider::class);
        $this->app->register(EventServiceProvider::class);

        $this->app->singleton(HealthCheckAggregator::class, function () {
            $aggregator = new HealthCheckAggregator();
            $aggregator->addCheck(new DatabaseHealthCheck());
            return $aggregator;
        });

        $this->app->bind(CircuitBreakerInterface::class, RedisCircuitBreaker::class);
        $this->app->bind(TransactionInterface::class, DatabaseTransaction::class);
        $this->app->bind(AuditInterface::class, SpatieAuditAdapter::class);
        $this->app->bind(ExportInterface::class, LaravelExportAdapter::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadHealthRoute();
    }

    private function loadHealthRoute(): void
    {
        \Illuminate\Support\Facades\Route::get('/health', \Shared\Infrastructure\Observability\HealthCheck\HealthCheckController::class)
            ->middleware('api');
    }
}
