<?php

declare(strict_types=1);

namespace Modules\Products\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Products\Domain\Ports\ProductRepositoryPort;
use Shared\Application\DTOs\BulkUuidsData;

final readonly class BulkDeleteProductsHandler
{
    public function __construct(private ProductRepositoryPort $products) {}

    #[\NoDiscard]
    public function handle(BulkUuidsData $data): int
    {
        return DB::transaction(fn () => $this->products->bulkSoftDeleteByUuid($data->uuids));
    }
}
