<?php

declare(strict_types=1);

namespace Modules\Products\Application\Queries;

use Illuminate\Support\Collection;
use Modules\Products\Domain\Ports\ProductMaterialRepositoryPort;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductEloquentModel;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductMaterialEloquentModel;

final readonly class ListProductMaterialsHandler
{
    public function __construct(private ProductMaterialRepositoryPort $materials) {}

    /**
     * @return Collection<int, ProductMaterialEloquentModel>
     */
    public function handle(ProductEloquentModel $product): Collection
    {
        return $this->materials->forProduct($product->id);
    }
}
