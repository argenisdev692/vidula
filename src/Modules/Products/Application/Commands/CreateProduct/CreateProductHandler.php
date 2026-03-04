<?php

declare(strict_types=1);

namespace Modules\Products\Application\Commands\CreateProduct;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\Products\Domain\Entities\Product;
use Modules\Products\Domain\Enums\ProductLevel;
use Modules\Products\Domain\Enums\ProductType;
use Modules\Products\Domain\Ports\ProductRepositoryPort;
use Modules\Products\Domain\ValueObjects\Money;
use Modules\Products\Domain\ValueObjects\ProductId;
use Modules\Products\Domain\ValueObjects\UserId;
use Shared\Infrastructure\Audit\AuditInterface;

/**
 * CreateProductHandler
 */
final readonly class CreateProductHandler
{
    public function __construct(
        private ProductRepositoryPort $repository,
        private AuditInterface $audit
    ) {
    }

    public function handle(CreateProductCommand $command): void
    {
        $dto = $command->dto;
        $uuid = Str::uuid()->toString();

        $product = Product::create(
            id: new ProductId($uuid),
            userId: new UserId($dto->userId),
            type: ProductType::from($dto->type),
            title: $dto->title,
            slug: $dto->slug,
            description: $dto->description,
            price: new Money($dto->price, $dto->currency),
            level: ProductLevel::from($dto->level),
            language: $dto->language,
            thumbnail: $dto->thumbnail
        );

        $this->repository->save($product);

        $this->audit->log(
            logName: 'products.product',
            description: 'Product created',
            properties: [
                'product_uuid' => $uuid,
                'title' => $dto->title,
                'type' => $dto->type,
            ]
        );

        // Clear cache
        try {
            Cache::tags(['products_list'])->flush();
        } catch (\Exception $e) {
            // Tags not supported, cache will expire naturally
        }
    }
}
