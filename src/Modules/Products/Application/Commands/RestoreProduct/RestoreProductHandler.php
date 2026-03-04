<?php

declare(strict_types=1);

namespace Modules\Products\Application\Commands\RestoreProduct;

use Modules\Products\Domain\Exceptions\ProductNotFoundException;
use Modules\Products\Domain\Ports\ProductRepositoryPort;
use Modules\Products\Domain\ValueObjects\ProductId;
use Shared\Infrastructure\Audit\AuditInterface;

final readonly class RestoreProductHandler
{
    public function __construct(
        private ProductRepositoryPort $repository,
        private AuditInterface $audit
    ) {
    }

    public function handle(RestoreProductCommand $command): void
    {
        $id = new ProductId($command->id);
        $this->repository->restore($id);

        $this->audit->log(
            logName: 'products.product',
            description: 'Product restored',
            properties: ['product_uuid' => $command->id]
        );
    }
}
