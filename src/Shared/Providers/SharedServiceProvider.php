<?php

declare(strict_types=1);

namespace Shared\Providers;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\View as ViewFactory;
use Illuminate\Support\ServiceProvider;
use Shared\Domain\Ports\AuditPort;
use Shared\Domain\Ports\ExportPort;
use Shared\Domain\Ports\SpeechSynthesizerPort;
use Shared\Domain\Ports\StoragePort;
use Shared\Infrastructure\AI\AIClientInterface;
use Shared\Infrastructure\AI\LaravelAIAdapter;
use Shared\Infrastructure\Audit\SpatieActivityLogAdapter;
use Shared\Infrastructure\Company\CompanyProfile;
use Shared\Infrastructure\Export\SimpleExcelExportAdapter;
use Shared\Infrastructure\Mail\BrevoMailAdapter;
use Shared\Infrastructure\Mail\MailInterface;
use Shared\Infrastructure\Research\TavilyClientInterface;
use Shared\Infrastructure\Research\TavilyResearchAdapter;
use Shared\Infrastructure\Resilience\CircuitBreaker\CircuitBreaker;
use Shared\Infrastructure\Resilience\CircuitBreaker\CircuitBreakerInterface;
use Shared\Infrastructure\Speech\ElevenLabsSpeechAdapter;
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
        $this->app->bind(AIClientInterface::class, LaravelAIAdapter::class);
        $this->app->bind(TavilyClientInterface::class, TavilyResearchAdapter::class);
        $this->app->bind(SpeechSynthesizerPort::class, ElevenLabsSpeechAdapter::class);
        $this->app->bind(MailInterface::class, BrevoMailAdapter::class);

        // Per-request CSP nonce shared between the SecurityHeaders middleware
        // and the Blade root view (`{{ app('csp-nonce') }}`).
        $this->app->scoped('csp-nonce', static fn (): string => base64_encode(random_bytes(16)));

        $this->app->singleton(
            CircuitBreakerInterface::class,
            static fn ($app): CircuitBreaker => new CircuitBreaker($app->make(Cache::class)),
        );
    }

    public function boot(): void
    {
        // Inject company branding (base64 logos + contact block) into the shared
        // corporate PDF layout so every export controller stays branding-agnostic.
        ViewFactory::composer('exports.pdf.layout', static function (View $view): void {
            $view->with('company', CompanyProfile::pdfBranding());
        });
    }
}
