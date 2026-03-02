<?php

declare(strict_types=1);

namespace Modules\Products\Application\Queries\GetProduct;

final readonly class GetProductQuery
{
    public function __construct(
        public string $uuid
    ) {}
}
