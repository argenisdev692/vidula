<?php

declare(strict_types=1);

namespace Modules\Users\Infrastructure\Persistence\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Users\Application\DTOs\UserFilterData;
use Modules\Users\Domain\Ports\UserRepositoryPort;
use Shared\Infrastructure\Persistence\Concerns\BulkSoftDeletesByUuid;

/**
 * Eloquent adapter for {@see UserRepositoryPort}, operating on the canonical
 * {@see User} auth model. Bulk soft-delete/restore are inherited from the Shared
 * {@see BulkSoftDeletesByUuid} trait (DRY).
 */
final readonly class EloquentUserRepository implements UserRepositoryPort
{
    use BulkSoftDeletesByUuid;

    /**
     * @return class-string<User>
     */
    protected function model(): string
    {
        return User::class;
    }

    public function paginate(UserFilterData $filters, int $perPage): LengthAwarePaginator
    {
        return User::query()
            ->applyFilters($filters)
            ->with(['roles:id,name'])
            ->select(['id', 'uuid', 'first_name', 'last_name', 'username', 'email', 'phone', 'address_2', 'email_verified_at', 'password', 'must_change_password', 'created_at', 'deleted_at'])
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findByUuid(string $uuid): ?User
    {
        return User::withTrashed()
            ->with(['roles:id,name', 'permissions:id,name'])
            ->where('uuid', $uuid)
            ->first();
    }

    public function createPending(array $attributes): User
    {
        return User::query()->create([...$attributes, 'password' => null]);
    }

    public function update(User $user, array $attributes): User
    {
        $user->update($attributes);

        return $user->refresh();
    }

    public function softDelete(string $uuid): bool
    {
        return (bool) User::query()->where('uuid', $uuid)->delete();
    }

    public function restore(string $uuid): bool
    {
        return (bool) User::onlyTrashed()->where('uuid', $uuid)->restore();
    }
}
