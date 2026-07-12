<?php

declare(strict_types=1);

namespace Modules\Post\Domain\Ports;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Post\Application\DTOs\PostFilterData;
use Modules\Post\Infrastructure\Persistence\Eloquent\Models\PostEloquentModel;

interface PostRepositoryPort
{
    /**
     * @return LengthAwarePaginator<int, PostEloquentModel>
     */
    public function paginate(PostFilterData $filters, int $perPage): LengthAwarePaginator;

    public function findByUuid(string $uuid): ?PostEloquentModel;

    /**
     * Landing-page feed: `published` posts only, optionally scoped to one
     * category by UUID.
     *
     * @return LengthAwarePaginator<int, PostEloquentModel>
     */
    public function paginatePublic(?string $categoryUuid, int $perPage): LengthAwarePaginator;

    /**
     * Landing-page single-post lookup: `published` only.
     */
    public function findPublicBySlug(string $slug): ?PostEloquentModel;

    public function slugExists(string $slug, ?string $ignoreUuid = null): bool;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): PostEloquentModel;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(PostEloquentModel $post, array $attributes): PostEloquentModel;

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
