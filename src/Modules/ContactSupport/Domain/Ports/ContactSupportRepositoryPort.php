<?php

declare(strict_types=1);

namespace Modules\ContactSupport\Domain\Ports;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\ContactSupport\Application\DTOs\ContactSupportFilterData;
use Modules\ContactSupport\Infrastructure\Persistence\Eloquent\Models\ContactSupportEloquentModel;

interface ContactSupportRepositoryPort
{
    /**
     * @return LengthAwarePaginator<int, ContactSupportEloquentModel>
     */
    public function paginate(ContactSupportFilterData $filters, int $perPage): LengthAwarePaginator;

    public function findByUuid(string $uuid): ?ContactSupportEloquentModel;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): ContactSupportEloquentModel;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(ContactSupportEloquentModel $contactSupport, array $attributes): ContactSupportEloquentModel;

    public function markAsRead(string $uuid): bool;

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
