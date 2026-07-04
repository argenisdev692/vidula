<?php

declare(strict_types=1);

namespace Modules\Users\Domain\Ports;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Users\Application\DTOs\UserFilterData;

/**
 * Persistence contract for the Users module. Operates on the canonical
 * {@see User} auth model (reused — never duplicated as a second Eloquent model).
 */
interface UserRepositoryPort
{
    /**
     * Paginated, filtered listing for the admin DataTable.
     *
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(UserFilterData $filters, int $perPage): LengthAwarePaginator;

    public function findByUuid(string $uuid): ?User;

    /**
     * Create a Pending user (no password) from invitation data.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function createPending(array $attributes): User;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(User $user, array $attributes): User;

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
