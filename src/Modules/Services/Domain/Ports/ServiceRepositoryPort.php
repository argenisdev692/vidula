<?php

declare(strict_types=1);

namespace Modules\Services\Domain\Ports;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Services\Application\DTOs\ServiceFilterData;
use Modules\Services\Infrastructure\Persistence\Eloquent\Models\ServiceEloquentModel;

interface ServiceRepositoryPort
{
    /**
     * @return LengthAwarePaginator<int, ServiceEloquentModel>
     */
    public function paginate(ServiceFilterData $filters, int $perPage): LengthAwarePaginator;

    /**
     * Public select-input feed: `is_active` records only, ordered for display.
     *
     * @return Collection<int, ServiceEloquentModel>
     */
    public function listActive(): Collection;

    public function findByUuid(string $uuid): ?ServiceEloquentModel;

    public function findActiveByUuid(string $uuid): ?ServiceEloquentModel;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): ServiceEloquentModel;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(ServiceEloquentModel $service, array $attributes): ServiceEloquentModel;

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
}
