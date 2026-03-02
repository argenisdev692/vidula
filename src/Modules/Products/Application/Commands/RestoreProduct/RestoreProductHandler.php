<?php

declare(strict_types=1);

namespace Modules\Product\Application\Commands\RestoreProduct;

use Modules\Product\Domain\Exceptions\ProductNotFoundException;
use Modules\Product\Domain\Ports\ProductRepositoryPort;
use Modules\Product\Domain\ValueObjects\ProductId;

final readonly class RestoreProductHandler
{
    public function __construct(
        private ProductRepositoryPort $repository
    ) {
    }

    public function handle(RestoreProductCommand $command): void
    {
        $id = new ProductId($command->id);
        $this->repository->restore($id);
    }
}
