<?php

declare(strict_types=1);

namespace Modules\Products\Application\Commands\RestoreProduct;

final readonly class RestoreProductCommand
{
    public function __construct(
        public string $id
    ) {
    }
}
