<?php

declare(strict_types=1);

namespace Modules\Products\Application\Commands\UpdateProduct;

use Illuminate\Support\Facades\Cache;
use Modules\Products\Domain\Enums\ProductLevel;
use Modules\Products\Domain\Exceptions\ProductNotFoundException;
use Modules\Products\Domain\Ports\ProductRepositoryPort;
use Modules\Products\Domain\ValueObjects\Money;
use Modules\Products\Domain\ValueObjects\ProductId;

final readonly class UpdateProductHandler
{
    public function __construct(
        private ProductRepositoryPort $repository
    ) {}

    public function handle(UpdateProductCommand $command): void
    {
        $product = $this->repository->findById(new ProductId($command->uuid));

        if (null === $product) {
            throw ProductNotFoundException::forId($command->uuid);
        }

        $dto = $command->dto;

        $updatedProduct = $product->update(
            title: $dto->title,
            slug: $dto->slug,
            description: $dto->description,
            price: new Money($dto->price, $dto->currency),
            level: ProductLevel::from($dto->level),
            language: $dto->language,
            thumbnail: $dto->thumbnail
        );

        $this->repository->save($updatedProduct);

        // Clear cache
        Cache::forget("product_{$command->uuid}");
        try {
            Cache::tags(['products_list'])->flush();
        } catch (\Exception $e) {
            // Tags not supported
        }
    }
}
