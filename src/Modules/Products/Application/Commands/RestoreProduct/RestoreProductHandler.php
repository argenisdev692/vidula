<?php

declare(strict_types=1);

namespace Modules\Products\Application\Commands\RestoreProduct;

use Modules\Products\Domain\Exceptions\ProductNotFoundException;
use Modules\Products\Domain\Ports\ProductRepositoryPort;
use Modules\Products\Domain\ValueObjects\ProductId;

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
