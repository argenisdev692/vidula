<?php

declare(strict_types=1);

namespace Modules\Cvs\Domain\Ports;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Cvs\Application\DTOs\CvFilterData;
use Modules\Cvs\Infrastructure\Persistence\Eloquent\Models\CvEloquentModel;

interface CvRepositoryPort
{
    public function paginate(CvFilterData $filters, int $perPage): LengthAwarePaginator;

    public function findByUuid(string $uuid): ?CvEloquentModel;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): CvEloquentModel;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(CvEloquentModel $cv, array $attributes): CvEloquentModel;

    public function softDelete(string $uuid): bool;

    public function restore(string $uuid): bool;

    /**
     * @param  array<int, string>  $uuids
     */
    public function bulkSoftDeleteByUuid(array $uuids): int;

    /**
     * @param  array<int, string>  $uuids
     */
    public function bulkRestoreByUuid(array $uuids): int;

    /**
     * Clears is_primary for every CV owned by $userId except an optional UUID.
     */
    public function clearPrimaryForUser(int $userId, ?string $exceptUuid = null): void;

    /**
     * Lightweight CV options for studio run start forms.
     *
     * @return list<array{uuid: string, title: string, niche: string, is_primary: bool}>
     */
    public function listSelectOptionsForUser(int $userId): array;
}
