<?php

declare(strict_types=1);

namespace Modules\Cvs\Infrastructure\Persistence\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Cvs\Application\DTOs\CvFilterData;
use Modules\Cvs\Domain\Ports\CvRepositoryPort;
use Modules\Cvs\Infrastructure\Persistence\Eloquent\Models\CvEloquentModel;
use Shared\Infrastructure\Persistence\Concerns\BulkSoftDeletesByUuid;

final class EloquentCvRepository implements CvRepositoryPort
{
    use BulkSoftDeletesByUuid;

    /**
     * @return class-string<CvEloquentModel>
     */
    protected function model(): string
    {
        return CvEloquentModel::class;
    }

    public function paginate(CvFilterData $filters, int $perPage): LengthAwarePaginator
    {
        return CvEloquentModel::query()
            ->when($filters->status === 'suspended', fn ($q) => $q->onlyTrashed())
            ->applyFilters($filters)
            ->with('user:id,first_name,last_name')
            ->select([
                'id',
                'uuid',
                'user_id',
                'title',
                'niche',
                'is_primary',
                'file_type',
                'original_filename',
                'created_at',
                'deleted_at',
            ])
            ->orderByDesc('is_primary')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findByUuid(string $uuid): ?CvEloquentModel
    {
        return CvEloquentModel::withTrashed()
            ->with('user:id,first_name,last_name')
            ->where('uuid', $uuid)
            ->first();
    }

    public function create(array $attributes): CvEloquentModel
    {
        return CvEloquentModel::query()->create($attributes);
    }

    public function update(CvEloquentModel $cv, array $attributes): CvEloquentModel
    {
        $cv->update($attributes);

        return $cv->refresh();
    }

    public function softDelete(string $uuid): bool
    {
        return (bool) CvEloquentModel::query()->where('uuid', $uuid)->delete();
    }

    public function restore(string $uuid): bool
    {
        return (bool) CvEloquentModel::onlyTrashed()->where('uuid', $uuid)->restore();
    }

    public function clearPrimaryForUser(int $userId, ?string $exceptUuid = null): void
    {
        CvEloquentModel::query()
            ->where('user_id', $userId)
            ->where('is_primary', true)
            ->when($exceptUuid !== null, fn ($q) => $q->where('uuid', '!=', $exceptUuid))
            ->update(['is_primary' => false]);
    }
}
