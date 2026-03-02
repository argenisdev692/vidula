<?php

declare(strict_types=1);

namespace Modules\Products\Application\Queries\ListProduct;

use Illuminate\Support\Facades\Cache;
use Modules\Products\Application\Queries\ReadModels\ProductReadModel;
use Modules\Products\Domain\Ports\ProductRepositoryPort;

final readonly class ListProductHandler
{
    public function __construct(
        private ProductRepositoryPort $repository
    ) {}

    /**
     * @return array{data: list<ProductReadModel>, total: int, perPage: int, currentPage: int, lastPage: int}
     */
    public function handle(ListProductQuery $query): array
    {
        $filters = $query->filters;
        $cacheKey = "products_list_" . md5(serialize($filters->toArray()));
        $ttl = 60 * 15;

        try {
            return Cache::tags(['products_list'])->remember($cacheKey, $ttl, function () use ($filters) {
                return $this->fetchData($filters);
            });
        } catch (\Exception $e) {
            return Cache::remember($cacheKey, $ttl, function () use ($filters) {
                return $this->fetchData($filters);
            });
        }
    }

    private function fetchData($filters): array
    {
        $result = $this->repository->findAllPaginated(
            filters: $filters->toArray(),
            page: $filters->page,
            perPage: $filters->perPage
        );

        $result['data'] = $result['data']
            |> (fn($products) => array_map(
                fn($product) => new ProductReadModel(
                    id: $product->id->value,
                    userId: $product->userId->value,
                    type: $product->type->value,
                    title: $product->title,
                    slug: $product->slug,
                    description: $product->description,
                    price: $product->price->amount,
                    currency: $product->price->currency,
                    status: $product->status->value,
                    thumbnail: $product->thumbnail,
                    level: $product->level->value,
                    language: $product->language,
                    createdAt: $product->createdAt,
                    updatedAt: $product->updatedAt,
                    deletedAt: $product->deletedAt
                ),
                $products
            ));

        return $result;
    }
}
