<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Infrastructure\Integrations;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\AiResumeStudio\Domain\Ports\JobPageScraperPort;
use RuntimeException;
use Shared\Infrastructure\Resilience\CircuitBreaker\CircuitBreakerInterface;
use Throwable;
use Uri\Rfc3986\Uri;

final readonly class FirecrawlJobPageScraperAdapter implements JobPageScraperPort
{
    public function __construct(private CircuitBreakerInterface $breaker) {}

    public function scrape(string $url): array
    {
        $apiKey = (string) config('cv_studio.firecrawl.api_key');

        if ($apiKey === '' || ! $this->isAllowedUrl($url)) {
            return ['markdown' => null, 'title' => null];
        }

        return $this->breaker->call(
            'firecrawl',
            function () use ($apiKey, $url): array {
                $response = Http::withToken($apiKey)
                    ->timeout((int) config('cv_studio.firecrawl.timeout_seconds'))
                    ->retry(1, 500)
                    ->post(rtrim((string) config('cv_studio.firecrawl.base_url'), '/').'/scrape', [
                        'url' => $url,
                        'formats' => ['markdown'],
                    ]);

                if ($response->failed()) {
                    throw new RuntimeException("Firecrawl scrape failed with status {$response->status()}.");
                }

                $data = (array) ($response->json('data') ?? []);

                return [
                    'markdown' => isset($data['markdown']) ? (string) $data['markdown'] : null,
                    'title' => isset($data['metadata']['title']) ? (string) $data['metadata']['title'] : null,
                ];
            },
            function (Throwable $e) use ($url): array {
                Log::warning('resume_studio.firecrawl.scrape_failed', [
                    'url_host' => Uri::parse($url)?->getHost(),
                    'error' => $e->getMessage(),
                ]);

                return ['markdown' => null, 'title' => null];
            },
        );
    }

    private function isAllowedUrl(string $url): bool
    {
        $uri = Uri::parse($url);

        if ($uri === null) {
            return false;
        }

        $scheme = $uri->getScheme();
        $host = $uri->getHost();

        if (! in_array($scheme, ['http', 'https'], true) || $host === null || $host === '') {
            return false;
        }

        $normalizedHost = strtolower($host);

        if (in_array($normalizedHost, ['localhost', '127.0.0.1', '::1', '0.0.0.0'], true)) {
            return false;
        }

        if (filter_var($normalizedHost, FILTER_VALIDATE_IP) !== false) {
            return filter_var(
                $normalizedHost,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
            ) !== false;
        }

        return ! str_ends_with($normalizedHost, '.local')
          && ! str_ends_with($normalizedHost, '.internal')
          && ! str_ends_with($normalizedHost, '.localhost');
    }
}
