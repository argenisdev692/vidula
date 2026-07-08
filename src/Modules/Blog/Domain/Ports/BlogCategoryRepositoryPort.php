<?php

declare(strict_types=1);

namespace Modules\Blog\Domain\Ports;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Blog\Application\DTOs\BlogCategoryFilterData;
use Modules\Blog\Infrastructure\Persistence\Eloquent\Models\BlogCategoryEloquentModel;

interface BlogCategoryRepositoryPort
{
    /**
     * @return LengthAwarePaginator<int, BlogCategoryEloquentModel>
     */
    public function paginate(BlogCategoryFilterData $filters, int $perPage): LengthAwarePaginator;

    public function findByUuid(string $uuid): ?BlogCategoryEloquentModel;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): BlogCategoryEloquentModel;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(BlogCategoryEloquentModel $category, array $attributes): BlogCategoryEloquentModel;

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
