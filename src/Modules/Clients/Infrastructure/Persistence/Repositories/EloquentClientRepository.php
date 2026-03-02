<?php

declare(strict_types=1);

namespace Modules\Clients\Infrastructure\Persistence\Repositories;

use Modules\Clients\Domain\Entities\Client;
use Modules\Clients\Domain\Ports\ClientRepositoryPort;
use Modules\Clients\Domain\ValueObjects\ClientId;
use Modules\Clients\Domain\ValueObjects\UserId;
use Modules\Clients\Infrastructure\Persistence\Eloquent\Models\ClientEloquentModel;
use Modules\Clients\Infrastructure\Persistence\Mappers\ClientMapper;
use Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserEloquentModel;
use Illuminate\Support\Facades\Cache;

/**
 * EloquentClientRepository
 */
final class EloquentClientRepository implements ClientRepositoryPort
{
    public function findById(ClientId $id): ?Client
    {
        $model = ClientEloquentModel::withTrashed()
            ->where('uuid', $id->value)
            ->first();

        return $model ? ClientMapper::toDomain($model) : null;
    }

    public function findByUserId(UserId $userId): ?Client
    {
        $user = UserEloquentModel::where('uuid', $userId->value)->first();

        if (!$user) {
            return null;
        }

        $model = ClientEloquentModel::withTrashed()
            ->where('user_id', $user->id)
            ->first();

        return $model ? ClientMapper::toDomain($model) : null;
    }

    public function save(Client $client): void
    {
        $model = ClientEloquentModel::withTrashed()
            ->where('uuid', $client->id->value)
            ->first() ?? new ClientEloquentModel();

        $user = UserEloquentModel::where('uuid', $client->userId->value)->firstOrFail();

        $model->fill([
            'uuid' => $client->id->value,
            'user_id' => $user->id,
            'company' => $client->companyName,
            'email' => $client->email,
            'phone' => $client->phone,
            'address' => $client->address,
            'website' => $client->socialLinks?->website,
            'facebook_link' => $client->socialLinks?->facebook,
            'instagram_link' => $client->socialLinks?->instagram,
            'linkedin_link' => $client->socialLinks?->linkedin,
            'twitter_link' => $client->socialLinks?->twitter,
            'latitude' => $client->coordinates?->latitude,
            'longitude' => $client->coordinates?->longitude,
            'deleted_at' => $client->deletedAt,
        ]);

        $model->save();

        // Flush cache tags
        try {
            Cache::tags(['clients_list'])->flush();
        } catch (\Exception $e) {
            // Tags not supported
        }
    }

    public function delete(ClientId $id): void
    {
        ClientEloquentModel::query()->where('uuid', $id->value)->delete();

        try {
            Cache::tags(['clients_list'])->flush();
        } catch (\Exception $e) {
        }
    }

    public function restore(ClientId $id): void
    {
        ClientEloquentModel::query()->withTrashed()->where('uuid', $id->value)->restore();

        try {
            Cache::tags(['clients_list'])->flush();
        } catch (\Exception $e) {
        }
    }

    public function findAllPaginated(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $query = ClientEloquentModel::query()
            ->when($filters['user_uuid'] ?? null, function ($q, $userUuid) {
                $user = UserEloquentModel::where('uuid', $userUuid)->first();
                return $user ? $q->where('user_id', $user->id) : $q->where('user_id', 0);
            })
            ->when($filters['search'] ?? null, fn($q, $search) => $q->where('company', 'like', "%{$search}%"))
            ->orderBy($filters['sort_by'] ?? 'created_at', $filters['sort_dir'] ?? 'desc');

        $paginator = $query->paginate(perPage: $perPage, page: $page);

        return [
            'data' => array_map(
                fn(ClientEloquentModel $model) => ClientMapper::toDomain($model),
                $paginator->items()
            ),
            'total' => $paginator->total(),
            'perPage' => $paginator->perPage(),
            'currentPage' => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
        ];
    }
}
