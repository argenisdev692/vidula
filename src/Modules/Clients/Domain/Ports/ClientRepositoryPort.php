<?php

declare(strict_types=1);

namespace Modules\Clients\Domain\Ports;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Clients\Application\DTOs\ClientFilterData;
use Modules\Clients\Infrastructure\Persistence\Eloquent\Models\ClientEloquentModel;

interface ClientRepositoryPort
{
    /** @return LengthAwarePaginator<int, ClientEloquentModel> */
    public function paginate(ClientFilterData $filters, int $perPage): LengthAwarePaginator;

    public function findByUuid(string $uuid): ?ClientEloquentModel;

    /** @param  array<string, mixed>  $attributes */
    public function create(array $attributes): ClientEloquentModel;

    /** @param  array<string, mixed>  $attributes */
    public function update(ClientEloquentModel $client, array $attributes): ClientEloquentModel;

    public function softDelete(string $uuid): bool;

    public function restore(string $uuid): bool;

    /** @param  array<int, string>  $uuids */
    public function bulkSoftDeleteByUuid(array $uuids): int;

    /** @param  array<int, string>  $uuids */
    public function bulkRestoreByUuid(array $uuids): int;
}
