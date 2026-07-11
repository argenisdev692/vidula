<?php

declare(strict_types=1);

namespace Modules\Portfolio\Domain\Ports;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Portfolio\Application\DTOs\PortfolioFilterData;
use Modules\Portfolio\Infrastructure\Persistence\Eloquent\Models\PortfolioEloquentModel;
use Modules\Portfolio\Infrastructure\Persistence\Eloquent\Models\PortfolioMediaEloquentModel;

interface PortfolioRepositoryPort
{
    /**
     * @return LengthAwarePaginator<int, PortfolioEloquentModel>
     */
    public function paginate(PortfolioFilterData $filters, int $perPage): LengthAwarePaginator;

    /**
     * Public landing-page feed: `is_public` records only, ordered for display.
     *
     * @return LengthAwarePaginator<int, PortfolioEloquentModel>
     */
    public function paginatePublic(int $perPage): LengthAwarePaginator;

    public function findByUuid(string $uuid): ?PortfolioEloquentModel;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): PortfolioEloquentModel;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(PortfolioEloquentModel $portfolio, array $attributes): PortfolioEloquentModel;

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

    public function addGalleryImage(PortfolioEloquentModel $portfolio, string $path, int $sortOrder): PortfolioMediaEloquentModel;

    public function deleteGalleryImage(PortfolioEloquentModel $portfolio, string $mediaUuid): bool;

    /**
     * @param  array<int, string>  $orderedUuids  gallery image UUIDs in their new display order
     */
    public function reorderGallery(PortfolioEloquentModel $portfolio, array $orderedUuids): void;
}
