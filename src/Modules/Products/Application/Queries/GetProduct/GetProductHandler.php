<?php

declare(strict_types=1);

namespace Modules\Products\Application\Queries\GetProduct;

use Illuminate\Support\Facades\Cache;
use Modules\Products\Application\Queries\ReadModels\ProductReadModel;
use Modules\Products\Domain\Exceptions\ProductNotFoundException;
use Modules\Products\Domain\Ports\ProductRepositoryPort;
use Modules\Products\Domain\ValueObjects\ProductId;

final readonly class GetProductHandler
{
    public function __construct(
        private ProductRepositoryPort $repository
    ) {}

    public function handle(GetProductQuery $query): ProductReadModel
    {
        $cacheKey = "product_{$query->uuid}";
        $ttl = 60 * 60; // 1 hour

        return Cache::remember($cacheKey, $ttl, function () use ($query) {
            $product = $this->repository->findById(new ProductId($query->uuid));

            if (null === $product) {
                throw ProductNotFoundException::forId($query->uuid);
            }

            return new ProductReadModel(
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
            );
        });
    }
}
