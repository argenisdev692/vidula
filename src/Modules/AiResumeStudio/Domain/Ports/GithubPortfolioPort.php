<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Domain\Ports;

interface GithubPortfolioPort
{
    /**
     * @param  list<string>  $selectedRepos  Full names (owner/repo) or repo names scoped to $username.
     * @return list<array{name: string, description: string|null, url: string, stars: int, language: string|null}>
     */
    public function listRepos(string $username, array $selectedRepos = []): array;
}
