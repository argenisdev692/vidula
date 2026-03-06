<?php

declare(strict_types=1);

namespace Modules\Users\Infrastructure\Persistence\Repositories;

use BackedEnum;
use Modules\Users\Domain\Entities\User;
use Modules\Users\Domain\Ports\UserRepositoryPort;
use Modules\Users\Infrastructure\Persistence\Mappers\UserMapper;
use Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserEloquentModel;

/**
 * EloquentUserRepository — Implements UserRepositoryPort using Eloquent.
 */
final class EloquentUserRepository implements UserRepositoryPort
{
    private const SELECT_COLUMNS = [
        'id',
        'uuid',
        'name',
        'last_name',
        'email',
        'username',
        'phone',
        'profile_photo_path',
        'address',
        'city',
        'state',
        'country',
        'zip_code',
        'status',
        'setup_token',
        'setup_token_expires_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function findByUuid(string $uuid): ?User
    {
        $model = UserEloquentModel::query()
            ->withTrashed()  // ✅ Include soft-deleted users
            ->select(self::SELECT_COLUMNS)
            ->where('uuid', $uuid)
            ->first();

        return $model ? UserMapper::toDomain($model) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $model = UserEloquentModel::query()
            ->select(self::SELECT_COLUMNS)
            ->where('email', $email)
            ->first();

        return $model ? UserMapper::toDomain($model) : null;
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{data: list<User>, total: int, perPage: int, currentPage: int, lastPage: int}
     */
    public function findAllPaginated(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $query = UserEloquentModel::query()
            ->select(self::SELECT_COLUMNS);

        // Handle soft deletes based on status filter
        $status = $filters['status'] ?? '';
        $status = $status instanceof BackedEnum ? $status->value : $status;

        if ($status === 'deleted') {
            $query->onlyTrashed();
        } elseif ($status === 'active' || $status === 'suspended' || $status === 'banned' || $status === 'pending_setup') {
            // Only non-deleted with specific status
            $query->where('status', $status);
        } else {
            // Show all (including soft-deleted)
            $query->withTrashed();
        }

        $query->when(
                $filters['search'] ?? null,
                fn($q, $search) => $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%");
                }),
            )
            ->when(
                ($filters['dateFrom'] ?? null) || ($filters['dateTo'] ?? null),
                fn($q) => $q->inDateRange($filters['dateFrom'] ?? null, $filters['dateTo'] ?? null),
            )
            ->orderBy(
                $filters['sortBy'] ?? 'created_at',
                $filters['sortDir'] ?? 'desc',
            );

        $paginator = $query->paginate(perPage: $perPage, page: $page);

        return [
            'data' => array_map(
                fn(UserEloquentModel $model) => UserMapper::toDomain($model),
                $paginator->items(),
            ),
            'total' => $paginator->total(),
            'perPage' => $paginator->perPage(),
            'currentPage' => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
        ];
    }

    public function search(string $query, int $limit = 10): array
    {
        return UserEloquentModel::query()
            ->select(self::SELECT_COLUMNS)
            ->where('name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->limit($limit)
            ->get()
            ->map(fn(UserEloquentModel $model) => UserMapper::toDomain($model))
            ->toArray();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): User
    {
        $model = UserEloquentModel::query()->create($data);

        return UserMapper::toDomain($model);
    }

    public function assignRole(string $uuid, string $role): void
    {
        $model = UserEloquentModel::query()->where('uuid', $uuid)->first();

        $model?->assignRole(strtoupper($role));
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $uuid, array $data): User
    {
        $model = UserEloquentModel::query()->where('uuid', $uuid)->firstOrFail();
        $model->update($data);
        $model->refresh();

        return UserMapper::toDomain($model);
    }

    public function save(User $user): void
    {
        $model = UserEloquentModel::query()->where('uuid', $user->uuid)->first();

        if ($model) {
            $model->update([
                'status' => $user->status->value,
                'setup_token' => $user->setupToken,
                'setup_token_expires_at' => $user->setupTokenExpiresAt,
                'deleted_at' => $user->deletedAt,
            ]);
        }
    }

    public function softDelete(string $uuid): void
    {
        $model = UserEloquentModel::query()->where('uuid', $uuid)->firstOrFail();
        $model->delete();
    }

    public function restore(string $uuid): void
    {
        $model = UserEloquentModel::query()->withTrashed()->where('uuid', $uuid)->firstOrFail();
        $model->restore();
    }
}
