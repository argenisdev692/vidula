<?php

declare(strict_types=1);

namespace Modules\Products\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Products\Domain\Ports\ProductRepositoryPort;

final readonly class DeleteProductHandler
{
    public function __construct(private ProductRepositoryPort $products) {}

    #[\NoDiscard]
    public function handle(string $uuid): bool
    {
        return DB::transaction(fn () => $this->products->softDelete($uuid));
    }
}
