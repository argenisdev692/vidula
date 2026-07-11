<?php

declare(strict_types=1);

namespace Modules\ContactSupport\Infrastructure\Persistence\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\ContactSupport\Application\DTOs\ContactSupportFilterData;
use Modules\ContactSupport\Domain\Ports\ContactSupportRepositoryPort;
use Modules\ContactSupport\Infrastructure\Persistence\Eloquent\Models\ContactSupportEloquentModel;
use Shared\Infrastructure\Persistence\Concerns\BulkSoftDeletesByUuid;

/**
 * Eloquent adapter for {@see ContactSupportRepositoryPort}. Bulk
 * soft-delete/restore are inherited from the Shared {@see BulkSoftDeletesByUuid}
 * trait (DRY).
 */
final readonly class EloquentContactSupportRepository implements ContactSupportRepositoryPort
{
    use BulkSoftDeletesByUuid;

    /**
     * @return class-string<ContactSupportEloquentModel>
     */
    protected function model(): string
    {
        return ContactSupportEloquentModel::class;
    }

    public function paginate(ContactSupportFilterData $filters, int $perPage): LengthAwarePaginator
    {
        return ContactSupportEloquentModel::query()
            ->when($filters->status === 'suspended', fn ($q) => $q->onlyTrashed())
            ->applyFilters($filters)
            ->select([
                'id',
                'uuid',
                'first_name',
                'last_name',
                'email',
                'phone',
                'subject',
                'sms_consent',
                'readed',
                'is_spam',
                'spam_score',
                'created_at',
                'deleted_at',
            ])
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findByUuid(string $uuid): ?ContactSupportEloquentModel
    {
        return ContactSupportEloquentModel::withTrashed()
            ->where('uuid', $uuid)
            ->first();
    }

    public function create(array $attributes): ContactSupportEloquentModel
    {
        return ContactSupportEloquentModel::query()->create($attributes);
    }

    public function update(ContactSupportEloquentModel $contactSupport, array $attributes): ContactSupportEloquentModel
    {
        $contactSupport->update($attributes);

        return $contactSupport->refresh();
    }

    public function markAsRead(string $uuid): bool
    {
        $contactSupport = ContactSupportEloquentModel::query()->where('uuid', $uuid)->first();

        if ($contactSupport === null) {
            return false;
        }

        $contactSupport->readed = true;

        return $contactSupport->save();
    }

    public function softDelete(string $uuid): bool
    {
        return (bool) ContactSupportEloquentModel::query()->where('uuid', $uuid)->delete();
    }

    public function restore(string $uuid): bool
    {
        return (bool) ContactSupportEloquentModel::onlyTrashed()->where('uuid', $uuid)->restore();
    }
}
