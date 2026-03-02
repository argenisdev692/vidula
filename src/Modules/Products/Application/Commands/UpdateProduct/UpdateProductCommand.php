<?php

declare(strict_types=1);

namespace Modules\Products\Application\Commands\UpdateProduct;

use Modules\Products\Application\DTOs\UpdateProductDTO;

final readonly class UpdateProductCommand
{
    public function __construct(
        public string $uuid,
        public UpdateProductDTO $dto
    ) {}
}
