<?php

declare(strict_types=1);

namespace Modules\Clients\Infrastructure\Persistence\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Clients\Application\DTOs\ClientFilterData;
use Modules\Clients\Domain\Ports\ClientRepositoryPort;
use Modules\Clients\Infrastructure\Persistence\Eloquent\Models\ClientEloquentModel;
use Shared\Infrastructure\Persistence\Concerns\BulkSoftDeletesByUuid;

final class EloquentClientRepository implements ClientRepositoryPort
{
    use BulkSoftDeletesByUuid;

    /**
     * @return class-string<ClientEloquentModel>
     */
    protected function model(): string
    {
        return ClientEloquentModel::class;
    }

    public function paginate(ClientFilterData $filters, int $perPage): LengthAwarePaginator
    {
        return ClientEloquentModel::query()
            ->when($filters->status === 'suspended', fn ($q) => $q->onlyTrashed())
            ->applyFilters($filters)
            ->with('user:id,first_name,last_name')
            ->select([
                'id',
                'uuid',
                'user_id',
                'client_name',
                'email',
                'status',
                'phone',
                'address',
                'tax_id',
                'nif',
                'website',
                'facebook_link',
                'instagram_link',
                'linkedin_link',
                'twitter_link',
                'notes',
                'created_at',
                'deleted_at',
            ])
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findByUuid(string $uuid): ?ClientEloquentModel
    {
        return ClientEloquentModel::withTrashed()
            ->with('user:id,first_name,last_name')
            ->where('uuid', $uuid)
            ->first();
    }

    public function create(array $attributes): ClientEloquentModel
    {
        return ClientEloquentModel::query()->create($attributes);
    }

    public function update(ClientEloquentModel $client, array $attributes): ClientEloquentModel
    {
        $client->update($attributes);

        return $client->refresh();
    }

    public function softDelete(string $uuid): bool
    {
        return (bool) ClientEloquentModel::query()->where('uuid', $uuid)->delete();
    }

    public function restore(string $uuid): bool
    {
        return (bool) ClientEloquentModel::onlyTrashed()->where('uuid', $uuid)->restore();
    }
}
