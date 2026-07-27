<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Infrastructure\Persistence\Repositories;

use Illuminate\Support\LazyCollection;
use Modules\AiResumeStudio\Application\DTOs\StudioFilterData;
use Modules\AiResumeStudio\Domain\Ports\JobMatchRepositoryPort;
use Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models\JobMatchEloquentModel;
use Shared\Infrastructure\Persistence\Concerns\BulkSoftDeletesByUuid;

final class EloquentJobMatchRepository implements JobMatchRepositoryPort
{
    use BulkSoftDeletesByUuid;

    protected function model(): string
    {
        return JobMatchEloquentModel::class;
    }

    public function findByUuid(string $uuid): ?JobMatchEloquentModel
    {
        return JobMatchEloquentModel::withTrashed()
            ->with('studioRun:id,uuid')
            ->where('uuid', $uuid)
            ->first();
    }

    public function create(array $attributes): JobMatchEloquentModel
    {
        return JobMatchEloquentModel::query()->create($attributes);
    }

    public function update(JobMatchEloquentModel $match, array $attributes): JobMatchEloquentModel
    {
        $match->update($attributes);

        return $match->refresh();
    }

    public function upsertByCanonicalUrl(int $userId, string $canonicalUrl, array $attributes): JobMatchEloquentModel
    {
        $existing = JobMatchEloquentModel::query()
            ->where('user_id', $userId)
            ->where('canonical_url', $canonicalUrl)
            ->first();

        if ($existing !== null) {
            $existing->update([
                'last_seen_at' => $attributes['last_seen_at'] ?? now(),
                'studio_run_id' => $attributes['studio_run_id'] ?? $existing->studio_run_id,
                'match_score' => $attributes['match_score'] ?? $existing->match_score,
                'match_reasoning' => $attributes['match_reasoning'] ?? $existing->match_reasoning,
                'raw_snippet' => $attributes['raw_snippet'] ?? $existing->raw_snippet,
                'raw_md' => $attributes['raw_md'] ?? $existing->raw_md,
            ]);

            return $existing->refresh();
        }

        return $this->create([
            ...$attributes,
            'user_id' => $userId,
            'canonical_url' => $canonicalUrl,
        ]);
    }

    public function lazyForExport(StudioFilterData $filters): LazyCollection
    {
        return JobMatchEloquentModel::query()
            ->when($filters->status === 'suspended', fn ($q) => $q->onlyTrashed())
            ->applyFilters($filters)
            ->with('user:id,first_name,last_name')
            ->orderByDesc('created_at')
            ->lazy();
    }
}
