<?php

declare(strict_types=1);

namespace Modules\SocialMedia\Infrastructure\Persistence\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\SocialMedia\Application\DTOs\SocialMediaContentFilterData;
use Modules\SocialMedia\Domain\Ports\SocialMediaContentRepositoryPort;
use Modules\SocialMedia\Infrastructure\Persistence\Eloquent\Models\SocialMediaContentEloquentModel;
use Shared\Infrastructure\Persistence\Concerns\BulkSoftDeletesByUuid;

/**
 * Eloquent adapter for {@see SocialMediaContentRepositoryPort}. Bulk
 * soft-delete/restore are inherited from the Shared
 * {@see BulkSoftDeletesByUuid} trait (DRY, same as EloquentPostRepository).
 */
final class EloquentSocialMediaContentRepository implements SocialMediaContentRepositoryPort
{
    use BulkSoftDeletesByUuid;

    /**
     * @return class-string<SocialMediaContentEloquentModel>
     */
    protected function model(): string
    {
        return SocialMediaContentEloquentModel::class;
    }

    public function paginate(SocialMediaContentFilterData $filters, int $perPage): LengthAwarePaginator
    {
        return SocialMediaContentEloquentModel::query()
            ->when($filters->status === 'suspended', fn ($q) => $q->onlyTrashed())
            ->applyFilters($filters)
            ->with('creator:id,first_name,last_name')
            ->select([
                'id', 'uuid', 'topic', 'status', 'business_goal', 'funnel_stage', 'language',
                'provider', 'overall_score_avg', 'all_scores_pass', 'quality_warning',
                'scheduled_at', 'published_at', 'created_by', 'created_at', 'deleted_at',
            ])
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findByUuid(string $uuid): ?SocialMediaContentEloquentModel
    {
        return SocialMediaContentEloquentModel::withTrashed()
            ->with('creator:id,first_name,last_name')
            ->where('uuid', $uuid)
            ->first();
    }

    public function dueForScheduledPublishing(): Collection
    {
        return SocialMediaContentEloquentModel::query()
            ->where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->get();
    }

    public function create(array $attributes): SocialMediaContentEloquentModel
    {
        return SocialMediaContentEloquentModel::query()->create($attributes);
    }

    public function update(SocialMediaContentEloquentModel $content, array $attributes): SocialMediaContentEloquentModel
    {
        $content->update($attributes);

        return $content->refresh();
    }

    public function softDelete(string $uuid): bool
    {
        return (bool) SocialMediaContentEloquentModel::query()->where('uuid', $uuid)->delete();
    }

    public function restore(string $uuid): bool
    {
        return (bool) SocialMediaContentEloquentModel::onlyTrashed()->where('uuid', $uuid)->restore();
    }
}
