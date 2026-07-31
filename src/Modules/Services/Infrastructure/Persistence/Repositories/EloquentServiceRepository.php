<?php

declare(strict_types=1);

namespace Modules\Services\Infrastructure\Persistence\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Services\Application\DTOs\ServiceFilterData;
use Modules\Services\Domain\Ports\ServiceRepositoryPort;
use Modules\Services\Infrastructure\Persistence\Eloquent\Models\ServiceEloquentModel;
use Shared\Infrastructure\Persistence\Concerns\BulkSoftDeletesByUuid;

/**
 * Eloquent adapter for {@see ServiceRepositoryPort}. Bulk soft-delete/restore
 * are inherited from the Shared {@see BulkSoftDeletesByUuid} trait (DRY).
 */
final readonly class EloquentServiceRepository implements ServiceRepositoryPort
{
    use BulkSoftDeletesByUuid;

    /**
     * @return class-string<ServiceEloquentModel>
     */
    protected function model(): string
    {
        return ServiceEloquentModel::class;
    }

    public function paginate(ServiceFilterData $filters, int $perPage): LengthAwarePaginator
    {
        return ServiceEloquentModel::query()
            ->when($filters->status === 'suspended', fn ($q) => $q->onlyTrashed())
            ->applyFilters($filters)
            ->with('user:id,first_name,last_name')
            ->select(['id', 'uuid', 'name', 'slug', 'description', 'is_active', 'sort_order', 'user_id', 'created_at', 'deleted_at'])
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function listActive(): Collection
    {
        return ServiceEloquentModel::query()
            ->active()
            ->select(['id', 'uuid', 'name', 'slug', 'description', 'sort_order'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function findByUuid(string $uuid): ?ServiceEloquentModel
    {
        return ServiceEloquentModel::withTrashed()
            ->where('uuid', $uuid)
            ->first();
    }

    public function findActiveByUuid(string $uuid): ?ServiceEloquentModel
    {
        return ServiceEloquentModel::query()
            ->active()
            ->where('uuid', $uuid)
            ->first();
    }

    public function create(array $attributes): ServiceEloquentModel
    {
        return ServiceEloquentModel::query()->create($attributes);
    }

    public function update(ServiceEloquentModel $service, array $attributes): ServiceEloquentModel
    {
        $service->update($attributes);

        return $service->refresh();
    }

    public function softDelete(string $uuid): bool
    {
        return (bool) ServiceEloquentModel::query()->where('uuid', $uuid)->delete();
    }

    public function restore(string $uuid): bool
    {
        return (bool) ServiceEloquentModel::onlyTrashed()->where('uuid', $uuid)->restore();
    }
}
