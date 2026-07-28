<?php

declare(strict_types=1);

namespace Modules\Products\Domain\Ports;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Products\Application\DTOs\ProductFilterData;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductEloquentModel;

interface ProductRepositoryPort
{
    /** @return LengthAwarePaginator<int, ProductEloquentModel> */
    public function paginate(ProductFilterData $filters, int $perPage): LengthAwarePaginator;

    public function findByUuid(string $uuid): ?ProductEloquentModel;

    /** @param  array<string, mixed>  $attributes */
    public function create(array $attributes): ProductEloquentModel;

    /** @param  array<string, mixed>  $attributes */
    public function update(ProductEloquentModel $product, array $attributes): ProductEloquentModel;

    public function softDelete(string $uuid): bool;

    public function restore(string $uuid): bool;

    /** @param  array<int, string>  $uuids */
    public function bulkSoftDeleteByUuid(array $uuids): int;

    /** @param  array<int, string>  $uuids */
    public function bulkRestoreByUuid(array $uuids): int;

    public function slugExists(string $slug, ?string $exceptUuid = null): bool;

    /**
     * Upsert the type-driven 1:1 detail row (classroom or video_course).
     *
     * @param  array<string, mixed>  $attributes
     */
    public function saveDetail(ProductEloquentModel $product, array $attributes): void;
}
