<?php

declare(strict_types=1);

namespace Modules\Products\Application\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Products\Application\DTOs\ProductFilterData;
use Modules\Products\Domain\Ports\ProductRepositoryPort;

final readonly class ListProductsHandler
{
    public function __construct(private ProductRepositoryPort $products) {}

    public function handle(ProductFilterData $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->products->paginate($filters, $perPage);
    }
}
