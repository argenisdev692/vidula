<?php

declare(strict_types=1);

namespace Modules\Products\Infrastructure\Persistence\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Modules\Products\Application\DTOs\ProductFilterData;
use Modules\Products\Domain\Enums\ProductType;
use Modules\Products\Domain\Ports\ProductRepositoryPort;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ClassroomEloquentModel;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductEloquentModel;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\VideoCourseEloquentModel;
use Shared\Infrastructure\Persistence\Concerns\BulkSoftDeletesByUuid;

final readonly class EloquentProductRepository implements ProductRepositoryPort
{
    use BulkSoftDeletesByUuid;

    /**
     * @return class-string<ProductEloquentModel>
     */
    protected function model(): string
    {
        return ProductEloquentModel::class;
    }

    public function paginate(ProductFilterData $filters, int $perPage): LengthAwarePaginator
    {
        return ProductEloquentModel::query()
            ->when($filters->status === 'suspended', fn ($q) => $q->onlyTrashed())
            ->applyFilters($filters)
            ->with([
                'user:id,first_name,last_name',
                'client:id,uuid,client_name',
                // 1:1 detail for edit dialog (explicit columns — Laravel 13 N+1 / over-fetch hygiene)
                'classroom:id,product_id,uuid,max_students,meet_url,objectives,requirements',
                'videoCourse:id,product_id,uuid,platform,playlist_url,total_videos,total_duration_minutes,target_audience',
            ])
            ->select([
                'id',
                'uuid',
                'user_id',
                'client_id',
                'type',
                'title',
                'slug',
                'description',
                'price',
                'currency',
                'status',
                'thumbnail',
                'level',
                'language',
                'start_date',
                'end_date',
                'total_hours',
                'total_sessions',
                'modality',
                'notes',
                'created_at',
                'deleted_at',
            ])
            ->orderBy($filters->resolvedSortField(), $filters->resolvedSortDirection())
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findByUuid(string $uuid): ?ProductEloquentModel
    {
        // Eager-load 1:1 detail + owner/client with explicit columns (Laravel 13
        // N+1 prevention — Model::shouldBeStrict() / preventLazyLoading).
        return ProductEloquentModel::withTrashed()
            ->with([
                'user:id,first_name,last_name',
                'client:id,uuid,client_name',
                'classroom',
                'videoCourse',
            ])
            ->withCount(['sessions', 'materials', 'contentGenerations'])
            ->where('uuid', $uuid)
            ->first();
    }

    public function create(array $attributes): ProductEloquentModel
    {
        return ProductEloquentModel::query()->create($attributes);
    }

    public function update(ProductEloquentModel $product, array $attributes): ProductEloquentModel
    {
        $product->update($attributes);

        return $product->refresh();
    }

    public function softDelete(string $uuid): bool
    {
        return (bool) ProductEloquentModel::query()->where('uuid', $uuid)->delete();
    }

    public function restore(string $uuid): bool
    {
        return (bool) ProductEloquentModel::onlyTrashed()->where('uuid', $uuid)->restore();
    }

    public function slugExists(string $slug, ?string $exceptUuid = null): bool
    {
        return ProductEloquentModel::withTrashed()
            ->where('slug', $slug)
            ->when($exceptUuid !== null, fn ($q) => $q->where('uuid', '!=', $exceptUuid))
            ->exists();
    }

    public function saveDetail(ProductEloquentModel $product, array $attributes): void
    {
        $type = $product->productType();

        if ($type->isVideo()) {
            VideoCourseEloquentModel::query()->updateOrCreate(
                ['product_id' => $product->id],
                [
                    'uuid' => $product->videoCourse?->uuid ?? (string) Str::uuid7(),
                    ...$attributes,
                ],
            );

            return;
        }

        if ($type === ProductType::Classroom) {
            ClassroomEloquentModel::query()->updateOrCreate(
                ['product_id' => $product->id],
                [
                    'uuid' => $product->classroom?->uuid ?? (string) Str::uuid7(),
                    ...$attributes,
                ],
            );
        }
    }
}
