<?php

declare(strict_types=1);

namespace Modules\Products\Application\Queries\ListProduct;

use Modules\Products\Application\DTOs\ProductFilterDTO;

final readonly class ListProductQuery
{
    public function __construct(
        public ProductFilterDTO $filters
    ) {}
}
