<?php

declare(strict_types=1);

namespace Modules\Products\Domain\Ports;

use Illuminate\Database\Eloquent\Collection;
use Modules\Products\Application\DTOs\GeneratedMaterialData;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductMaterialEloquentModel;

/**
 * Product materials: operator CRUD + pipeline-generated course.md / course.pdf.
 */
interface ProductMaterialRepositoryPort
{
    /** @return Collection<int, ProductMaterialEloquentModel> */
    public function listByProduct(int $productId): Collection;

    public function findByUuid(string $uuid): ?ProductMaterialEloquentModel;

    /**
     * Product-scoped lookup so a material UUID from another product cannot be
     * reached through a foreign product route (OWASP API1).
     */
    public function findByUuidForProduct(string $uuid, int $productId): ?ProductMaterialEloquentModel;

    /** @param  array<string, mixed>  $attributes */
    public function create(array $attributes): ProductMaterialEloquentModel;

    /** @param  array<string, mixed>  $attributes */
    public function update(
        ProductMaterialEloquentModel $material,
        array $attributes,
    ): ProductMaterialEloquentModel;

    /**
     * @param  list<GeneratedMaterialData>  $materials
     */
    public function replaceGenerated(string $productUuid, array $materials): void;
}
