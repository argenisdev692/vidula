<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Infrastructure\Persistence\Repositories;

use Modules\AiResumeStudio\Domain\Ports\GithubEnrichmentRepositoryPort;
use Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models\GithubEnrichmentEloquentModel;

final class EloquentGithubEnrichmentRepository implements GithubEnrichmentRepositoryPort
{
    public function create(array $attributes): GithubEnrichmentEloquentModel
    {
        return GithubEnrichmentEloquentModel::query()->create($attributes);
    }
}
