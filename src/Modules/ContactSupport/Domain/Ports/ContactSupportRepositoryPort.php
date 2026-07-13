<?php

declare(strict_types=1);

namespace Modules\ContactSupport\Domain\Ports;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
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
     * Unread count for the navbar notification bell.
     */
    public function countUnread(): int;

    /**
     * Most recent submissions for the navbar notification bell.
     *
     * @return Collection<int, ContactSupportEloquentModel>
     */
    public function recent(int $limit): Collection;

    /**
     * Flips every unread row to `readed = true`.
     *
     * @return int number of rows updated
     */
    public function markAllAsRead(): int;

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
