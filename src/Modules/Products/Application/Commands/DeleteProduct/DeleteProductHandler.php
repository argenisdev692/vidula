<?php

declare(strict_types=1);

namespace Modules\Products\Application\Commands\DeleteProduct;

use Illuminate\Support\Facades\Cache;
use Modules\Products\Domain\Exceptions\ProductNotFoundException;
use Modules\Products\Domain\Ports\ProductRepositoryPort;
use Modules\Products\Domain\ValueObjects\ProductId;
use Shared\Infrastructure\Audit\AuditInterface;

final readonly class DeleteProductHandler
{
    public function __construct(
        private ProductRepositoryPort $repository,
        private AuditInterface $audit
    ) {
    }

    public function handle(DeleteProductCommand $command): void
    {
        $id = new ProductId($command->id);
        $product = $this->repository->findById($id);

        if (null === $product) {
            throw ProductNotFoundException::forId($command->id);
        }

        $this->repository->delete($id);

        $this->audit->log(
            logName: 'products.product',
            description: 'Product soft-deleted',
            properties: ['product_uuid' => $command->id]
        );

        // Clear caches
        Cache::forget("product_{$command->id}");
        try {
            Cache::tags(['products_list'])->flush();
        } catch (\Exception $e) {
            // Tags not supported
        }
    }
}
