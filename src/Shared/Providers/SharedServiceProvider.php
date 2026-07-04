<?php

declare(strict_types=1);

namespace Shared\Providers;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\ServiceProvider;
use Shared\Domain\Ports\AuditPort;
use Shared\Domain\Ports\ExportPort;
use Shared\Domain\Ports\StoragePort;
use Shared\Infrastructure\Audit\SpatieActivityLogAdapter;
use Shared\Infrastructure\Export\SimpleExcelExportAdapter;
use Shared\Infrastructure\Resilience\CircuitBreaker\CircuitBreaker;
use Shared\Infrastructure\Resilience\CircuitBreaker\CircuitBreakerInterface;
use Shared\Infrastructure\Storage\R2StorageAdapter;

/**
 * Wires the cross-cutting Shared ports to their concrete adapters.
 * Registered in bootstrap/providers.php.
 */
final class SharedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AuditPort::class, SpatieActivityLogAdapter::class);
        $this->app->bind(StoragePort::class, R2StorageAdapter::class);
        // CSV/Excel handled here; PDF delegated to DomPdfExportAdapter (auto-resolved).
        $this->app->bind(ExportPort::class, SimpleExcelExportAdapter::class);

        // Per-request CSP nonce shared between the SecurityHeaders middleware
        // and the Blade root view (`{{ app('csp-nonce') }}`).
        $this->app->scoped('csp-nonce', static fn (): string => base64_encode(random_bytes(16)));

        $this->app->singleton(
            CircuitBreakerInterface::class,
            static fn ($app): CircuitBreaker => new CircuitBreaker($app->make(Cache::class)),
        );
    }
}
