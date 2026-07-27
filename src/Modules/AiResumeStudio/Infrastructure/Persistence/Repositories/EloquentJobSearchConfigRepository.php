<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Infrastructure\Persistence\Repositories;

use Modules\AiResumeStudio\Domain\Enums\JobSearchConfigStatus;
use Modules\AiResumeStudio\Domain\Ports\JobSearchConfigRepositoryPort;
use Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models\JobSearchConfigEloquentModel;
use Shared\Infrastructure\Persistence\Concerns\BulkSoftDeletesByUuid;

final class EloquentJobSearchConfigRepository implements JobSearchConfigRepositoryPort
{
    use BulkSoftDeletesByUuid;

    protected function model(): string
    {
        return JobSearchConfigEloquentModel::class;
    }

    public function findByUuid(string $uuid): ?JobSearchConfigEloquentModel
    {
        return JobSearchConfigEloquentModel::withTrashed()
            ->with('cv:id,uuid,title')
            ->where('uuid', $uuid)
            ->first();
    }

    public function create(array $attributes): JobSearchConfigEloquentModel
    {
        return JobSearchConfigEloquentModel::query()->create($attributes);
    }

    public function update(JobSearchConfigEloquentModel $config, array $attributes): JobSearchConfigEloquentModel
    {
        $config->update($attributes);

        return $config->refresh();
    }

    public function findScheduledEnabled(): array
    {
        return JobSearchConfigEloquentModel::query()
            ->where('schedule_enabled', true)
            ->where('status', JobSearchConfigStatus::Active->value)
            ->with('cv:id,uuid,title,niche,raw_text')
            ->get()
            ->all();
    }

    public function recentForListing(int $limit = 20): array
    {
        return JobSearchConfigEloquentModel::query()
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->all();
    }
}
