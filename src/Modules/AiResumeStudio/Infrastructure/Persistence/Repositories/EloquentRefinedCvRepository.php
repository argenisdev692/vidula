<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Infrastructure\Persistence\Repositories;

use Modules\AiResumeStudio\Domain\Ports\RefinedCvRepositoryPort;
use Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models\RefinedCvEloquentModel;

final class EloquentRefinedCvRepository implements RefinedCvRepositoryPort
{
    public function create(array $attributes): RefinedCvEloquentModel
    {
        return RefinedCvEloquentModel::query()->create($attributes);
    }

    public function findByUuid(string $uuid): ?RefinedCvEloquentModel
    {
        return RefinedCvEloquentModel::query()->where('uuid', $uuid)->first();
    }

    public function nextVersionForCv(int $cvId): int
    {
        $latest = RefinedCvEloquentModel::query()
            ->where('cv_id', $cvId)
            ->max('version');

        return ((int) $latest) + 1;
    }
}
