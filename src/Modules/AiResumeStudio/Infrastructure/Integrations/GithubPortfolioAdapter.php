<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Infrastructure\Integrations;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\AiResumeStudio\Domain\Ports\GithubPortfolioPort;
use RuntimeException;
use Shared\Infrastructure\Resilience\CircuitBreaker\CircuitBreakerInterface;
use Throwable;

final readonly class GithubPortfolioAdapter implements GithubPortfolioPort
{
    public function __construct(private CircuitBreakerInterface $breaker) {}

    public function listRepos(string $username, array $selectedRepos = []): array
    {
        if ($username === '') {
            return [];
        }

        $token = (string) config('cv_studio.github.token');

        return $this->breaker->call(
            'github',
            function () use ($username, $selectedRepos, $token): array {
                $request = Http::baseUrl((string) config('cv_studio.github.api_url'))
                    ->timeout((int) config('cv_studio.github.timeout_seconds'))
                    ->retry(1, 500)
                    ->accept('application/vnd.github+json');

                if ($token !== '') {
                    $request = $request->withToken($token);
                }

                $response = $request->get('/users/'.rawurlencode($username).'/repos', [
                    'per_page' => 100,
                    'sort' => 'updated',
                ]);

                if ($response->failed()) {
                    throw new RuntimeException("GitHub repos list failed with status {$response->status()}.");
                }

                $repos = collect($response->json())
                    ->map(static fn (array $repo): array => [
                        'name' => (string) ($repo['full_name'] ?? $repo['name'] ?? ''),
                        'description' => isset($repo['description']) ? (string) $repo['description'] : null,
                        'url' => (string) ($repo['html_url'] ?? ''),
                        'stars' => (int) ($repo['stargazers_count'] ?? 0),
                        'language' => isset($repo['language']) ? (string) $repo['language'] : null,
                    ])
                    ->filter(static fn (array $repo): bool => $repo['name'] !== '')
                    ->values();

                if ($selectedRepos !== []) {
                    $selected = collect($selectedRepos)->map(static fn (string $name): string => strtolower($name));
                    $repos = $repos->filter(
                        static fn (array $repo): bool => $selected->contains(strtolower($repo['name']))
                          || $selected->contains(strtolower((string) strrchr($repo['name'], '/') ?: $repo['name'])),
                    )->values();
                }

                return $repos->all();
            },
            function (Throwable $e) use ($username): array {
                Log::warning('resume_studio.github.list_failed', [
                    'username' => $username,
                    'error' => $e->getMessage(),
                ]);

                return [];
            },
        );
    }
}
