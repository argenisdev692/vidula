<?php

declare(strict_types=1);

namespace Modules\Products\Application\Commands\CreateProduct;

use Modules\Products\Application\DTOs\CreateProductDTO;

final readonly class CreateProductCommand
{
    public function __construct(
        public CreateProductDTO $dto
    ) {}
}
