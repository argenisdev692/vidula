<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Domain\Ports;

use Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models\GithubEnrichmentEloquentModel;

interface GithubEnrichmentRepositoryPort
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): GithubEnrichmentEloquentModel;
}
