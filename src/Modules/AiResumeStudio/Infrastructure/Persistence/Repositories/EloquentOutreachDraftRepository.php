<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Infrastructure\Persistence\Repositories;

use Modules\AiResumeStudio\Domain\Ports\OutreachDraftRepositoryPort;
use Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models\OutreachDraftEloquentModel;
use Shared\Infrastructure\Persistence\Concerns\BulkSoftDeletesByUuid;

final readonly class EloquentOutreachDraftRepository implements OutreachDraftRepositoryPort
{
    use BulkSoftDeletesByUuid;

    protected function model(): string
    {
        return OutreachDraftEloquentModel::class;
    }

    public function findByUuid(string $uuid): ?OutreachDraftEloquentModel
    {
        return OutreachDraftEloquentModel::withTrashed()
            ->with('jobMatch:id,uuid,job_title')
            ->where('uuid', $uuid)
            ->first();
    }

    public function create(array $attributes): OutreachDraftEloquentModel
    {
        return OutreachDraftEloquentModel::query()->create($attributes);
    }

    public function update(OutreachDraftEloquentModel $draft, array $attributes): OutreachDraftEloquentModel
    {
        $draft->update($attributes);

        return $draft->refresh();
    }
}
