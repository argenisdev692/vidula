<?php

declare(strict_types=1);

namespace Modules\Products\Application\Commands\DeleteProduct;

final readonly class DeleteProductCommand
{
    public function __construct(
        public string $id
    ) {}
}
