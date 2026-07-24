<?php

declare(strict_types=1);

namespace Modules\Students\Domain\Ports;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Students\Application\DTOs\StudentFilterData;
use Modules\Students\Infrastructure\Persistence\Eloquent\Models\StudentEloquentModel;

interface StudentRepositoryPort
{
    /** @return LengthAwarePaginator<int, StudentEloquentModel> */
    public function paginate(StudentFilterData $filters, int $perPage): LengthAwarePaginator;

    public function findByUuid(string $uuid): ?StudentEloquentModel;

    /** @param  array<string, mixed>  $attributes */
    public function create(array $attributes): StudentEloquentModel;

    /** @param  array<string, mixed>  $attributes */
    public function update(StudentEloquentModel $student, array $attributes): StudentEloquentModel;

    public function softDelete(string $uuid): bool;

    public function restore(string $uuid): bool;

    /** @param  array<int, string>  $uuids */
    public function bulkSoftDeleteByUuid(array $uuids): int;

    /** @param  array<int, string>  $uuids */
    public function bulkRestoreByUuid(array $uuids): int;
}
