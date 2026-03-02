<?php

declare(strict_types=1);

namespace Modules\Product\Application\Commands\RestoreProduct;

final readonly class RestoreProductCommand
{
    public function __construct(
        public string $id
    ) {
    }
}
