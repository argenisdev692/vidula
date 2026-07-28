<?php

declare(strict_types=1);

namespace Modules\Products\Application\Queries;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Products\Domain\Ports\ProductRepositoryPort;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductEloquentModel;

/**
 * Single product with its 1:1 detail (classroom / video course). The repository
 * eager-loads relations + withCount so the detail view stays a fixed number of
 * queries (BACKEND-PHP §4.1 / Laravel 13 Eloquent N+1 prevention).
 */
final readonly class GetProductHandler
{
    public function __construct(private ProductRepositoryPort $products) {}

    public function handle(string $uuid): ProductEloquentModel
    {
        return $this->products->findByUuid($uuid)
            ?? throw (new ModelNotFoundException)->setModel(ProductEloquentModel::class, [$uuid]);
    }
}
