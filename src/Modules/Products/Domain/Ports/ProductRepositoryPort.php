<?php

declare(strict_types=1);

namespace Modules\Products\Domain\Ports;

use Modules\Products\Domain\Entities\Product;
use Modules\Products\Domain\ValueObjects\ProductId;

/**
 * ProductRepositoryPort
 */
interface ProductRepositoryPort
{
    public function findById(ProductId $id): ?Product;

    public function findBySlug(string $slug): ?Product;

    public function save(Product $product): void;

    public function delete(ProductId $id): void;

    public function restore(ProductId $id): void;

    /**
     * @param array<string, mixed> $filters
     */
    public function findAllPaginated(array $filters = [], int $page = 1, int $perPage = 15): array;
}
