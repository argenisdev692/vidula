<?php

declare(strict_types=1);

namespace Modules\Portfolio\Infrastructure\Persistence\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Portfolio\Application\DTOs\PortfolioFilterData;
use Modules\Portfolio\Domain\Ports\PortfolioRepositoryPort;
use Modules\Portfolio\Infrastructure\Persistence\Eloquent\Models\PortfolioEloquentModel;
use Modules\Portfolio\Infrastructure\Persistence\Eloquent\Models\PortfolioMediaEloquentModel;
use Shared\Infrastructure\Persistence\Concerns\BulkSoftDeletesByUuid;

/**
 * Eloquent adapter for {@see PortfolioRepositoryPort}. Bulk soft-delete/restore
 * are inherited from the Shared {@see BulkSoftDeletesByUuid} trait (DRY).
 */
final class EloquentPortfolioRepository implements PortfolioRepositoryPort
{
    use BulkSoftDeletesByUuid;

    /**
     * @return class-string<PortfolioEloquentModel>
     */
    protected function model(): string
    {
        return PortfolioEloquentModel::class;
    }

    public function paginate(PortfolioFilterData $filters, int $perPage): LengthAwarePaginator
    {
        return PortfolioEloquentModel::query()
            ->when($filters->status === 'suspended', fn ($q) => $q->onlyTrashed())
            ->applyFilters($filters)
            ->with('user:id,first_name,last_name')
            ->withCount('gallery')
            ->select([
                'id',
                'uuid',
                'title',
                'client_name',
                'project_type',
                'live_url',
                'published_at',
                'is_public',
                'cover_path',
                'video_path',
                'sort_order',
                'user_id',
                'created_at',
                'deleted_at',
            ])
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function paginatePublic(int $perPage): LengthAwarePaginator
    {
        // No `user` eager-load: the public ReadModel does not expose the author,
        // so loading first_name/last_name would only pull PII the feed discards.
        // `id` stays selected — it is the local key the `gallery` HasMany needs.
        return PortfolioEloquentModel::query()
            ->public()
            ->with(['gallery:id,uuid,portfolio_id,path,sort_order'])
            ->select([
                'id',
                'uuid',
                'title',
                'client_name',
                'project_type',
                'live_url',
                'published_at',
                'cover_path',
                'video_path',
                'description',
                'sort_order',
            ])
            ->orderBy('sort_order')
            ->orderByDesc('published_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findByUuid(string $uuid): ?PortfolioEloquentModel
    {
        return PortfolioEloquentModel::withTrashed()
            ->with('gallery')
            ->where('uuid', $uuid)
            ->first();
    }

    public function create(array $attributes): PortfolioEloquentModel
    {
        return PortfolioEloquentModel::query()->create($attributes);
    }

    public function update(PortfolioEloquentModel $portfolio, array $attributes): PortfolioEloquentModel
    {
        $portfolio->update($attributes);

        return $portfolio->refresh();
    }

    public function softDelete(string $uuid): bool
    {
        return (bool) PortfolioEloquentModel::query()->where('uuid', $uuid)->delete();
    }

    public function restore(string $uuid): bool
    {
        return (bool) PortfolioEloquentModel::onlyTrashed()->where('uuid', $uuid)->restore();
    }

    public function addGalleryImage(PortfolioEloquentModel $portfolio, string $path, int $sortOrder): PortfolioMediaEloquentModel
    {
        return $portfolio->gallery()->create([
            'path' => $path,
            'sort_order' => $sortOrder,
        ]);
    }

    public function deleteGalleryImage(PortfolioEloquentModel $portfolio, string $mediaUuid): bool
    {
        return (bool) $portfolio->gallery()->where('uuid', $mediaUuid)->delete();
    }

    public function reorderGallery(PortfolioEloquentModel $portfolio, array $orderedUuids): void
    {
        foreach ($orderedUuids as $index => $uuid) {
            $portfolio->gallery()->where('uuid', $uuid)->update(['sort_order' => $index]);
        }
    }
}
