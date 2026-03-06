<?php

declare(strict_types=1);

namespace Modules\CompanyData\Infrastructure\Persistence\Repositories;

use Modules\CompanyData\Domain\Entities\CompanyData;
use Modules\CompanyData\Domain\Enums\CompanyStatus;
use Modules\CompanyData\Domain\Ports\CompanyDataRepositoryPort;
use Modules\CompanyData\Domain\ValueObjects\CompanyDataId;
use Modules\CompanyData\Domain\ValueObjects\UserId;
use Modules\CompanyData\Infrastructure\Persistence\Eloquent\Models\CompanyDataEloquentModel;
use Modules\CompanyData\Infrastructure\Persistence\Mappers\CompanyDataMapper;
use Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserEloquentModel;

/**
 * EloquentCompanyDataRepository
 */
final class EloquentCompanyDataRepository implements CompanyDataRepositoryPort
{
    public function findById(CompanyDataId $id): ?CompanyData
    {
        $model = CompanyDataEloquentModel::withTrashed()
            ->where('uuid', $id->value)
            ->first();

        return $model ? CompanyDataMapper::toDomain($model) : null;
    }

    public function findByUserId(UserId $userId): ?CompanyData
    {
        // Lookup user by uuid to get the internal ID
        $user = UserEloquentModel::where('uuid', $userId->value)->first();

        if (!$user) {
            return null;
        }

        $model = CompanyDataEloquentModel::withTrashed()
            ->where('user_id', $user->id)
            ->first();

        return $model ? CompanyDataMapper::toDomain($model) : null;
    }

    public function existsAny(): bool
    {
        return CompanyDataEloquentModel::withTrashed()->exists();
    }

    public function save(CompanyData $companyData): void
    {
        $model = CompanyDataEloquentModel::withTrashed()
            ->where('uuid', $companyData->id->value)
            ->first() ?? new CompanyDataEloquentModel();

        $user = UserEloquentModel::where('uuid', $companyData->userId->value)->firstOrFail();

        $socialLinks = $companyData->socialLinks->toArray();
        $coords = $companyData->coordinates->toArray();

        $model->fill([
            'uuid' => $companyData->id->value,
            'user_id' => $user->id,
            'company_name' => $companyData->companyName,
            'name' => $companyData->name,
            'email' => $companyData->email,
            'phone' => $companyData->phone,
            'address' => $companyData->address,
            'website' => $socialLinks['website'],
            'facebook_link' => $socialLinks['facebook'],
            'instagram_link' => $socialLinks['instagram'],
            'linkedin_link' => $socialLinks['linkedin'],
            'twitter_link' => $socialLinks['twitter'],
            'latitude' => $coords['latitude'],
            'longitude' => $coords['longitude'],
            'signature_path' => $companyData->signaturePath,
            'status' => $companyData->status->value,
            'deleted_at' => $companyData->deletedAt,
        ]);

        $model->save();
    }

    public function delete(CompanyDataId $id): void
    {
        CompanyDataEloquentModel::query()->where('uuid', $id->value)->delete();
    }

    public function restore(CompanyDataId $id): void
    {
        CompanyDataEloquentModel::query()->withTrashed()->where('uuid', $id->value)->restore();
    }

    public function findAllPaginated(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $userUuid = $filters['user_uuid'] ?? $filters['userUuid'] ?? null;
        $dateFrom = $filters['date_from'] ?? $filters['dateFrom'] ?? null;
        $dateTo = $filters['date_to'] ?? $filters['dateTo'] ?? null;
        $sortBy = $filters['sort_by'] ?? $filters['sortBy'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? $filters['sortDir'] ?? 'desc';

        $query = CompanyDataEloquentModel::query()
            ->withTrashed()
            ->when($userUuid, function ($q, $userUuid) {
                $user = UserEloquentModel::where('uuid', $userUuid)->first();
                return $user ? $q->where('user_id', $user->id) : $q->where('user_id', 0);
            })
            ->inDateRange($dateFrom, $dateTo)
            ->when($filters['search'] ?? null, fn($q, $search) => $q->where('company_name', 'like', "%{$search}%"))
            ->orderBy($sortBy, $sortDir);

        $paginator = $query->paginate(perPage: $perPage, page: $page);

        return [
            'data' => array_map(
                fn(CompanyDataEloquentModel $model) => CompanyDataMapper::toDomain($model),
                $paginator->items()
            ),
            'total' => $paginator->total(),
            'perPage' => $paginator->perPage(),
            'currentPage' => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
        ];
    }
}
