<?php

declare(strict_types=1);

namespace Modules\Students\Infrastructure\Persistence\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Students\Application\DTOs\StudentFilterData;
use Modules\Students\Domain\Ports\StudentRepositoryPort;
use Modules\Students\Infrastructure\Persistence\Eloquent\Models\StudentEloquentModel;
use Shared\Infrastructure\Persistence\Concerns\BulkSoftDeletesByUuid;

final class EloquentStudentRepository implements StudentRepositoryPort
{
    use BulkSoftDeletesByUuid;

    /**
     * @return class-string<StudentEloquentModel>
     */
    protected function model(): string
    {
        return StudentEloquentModel::class;
    }

    public function paginate(StudentFilterData $filters, int $perPage): LengthAwarePaginator
    {
        return StudentEloquentModel::query()
            ->when($filters->status === 'suspended', fn ($q) => $q->onlyTrashed())
            ->applyFilters($filters)
            ->select([
                'id',
                'uuid',
                'name',
                'email',
                'phone',
                'dni',
                'address',
                'avatar',
                'notes',
                'status',
                'active',
                'created_at',
                'deleted_at',
            ])
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findByUuid(string $uuid): ?StudentEloquentModel
    {
        return StudentEloquentModel::withTrashed()
            ->where('uuid', $uuid)
            ->first();
    }

    public function create(array $attributes): StudentEloquentModel
    {
        return StudentEloquentModel::query()->create($attributes);
    }

    public function update(StudentEloquentModel $student, array $attributes): StudentEloquentModel
    {
        $student->update($attributes);

        return $student->refresh();
    }

    public function softDelete(string $uuid): bool
    {
        return (bool) StudentEloquentModel::query()->where('uuid', $uuid)->delete();
    }

    public function restore(string $uuid): bool
    {
        return (bool) StudentEloquentModel::onlyTrashed()->where('uuid', $uuid)->restore();
    }
}
