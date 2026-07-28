<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Infrastructure\Persistence\Repositories;

use Modules\AiResumeStudio\Domain\Ports\GithubEnrichmentRepositoryPort;
use Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models\GithubEnrichmentEloquentModel;

final readonly class EloquentGithubEnrichmentRepository implements GithubEnrichmentRepositoryPort
{
    public function create(array $attributes): GithubEnrichmentEloquentModel
    {
        return GithubEnrichmentEloquentModel::query()->create($attributes);
    }

    public function latestForUserCv(int $userId, int $cvId): ?GithubEnrichmentEloquentModel
    {
        return GithubEnrichmentEloquentModel::query()
            ->where('cv_id', $cvId)
            ->where('user_id', $userId)
            ->latest('id')
            ->first();
    }

    public function update(GithubEnrichmentEloquentModel $enrichment, array $attributes): GithubEnrichmentEloquentModel
    {
        $enrichment->update($attributes);

        return $enrichment->refresh();
    }
}
