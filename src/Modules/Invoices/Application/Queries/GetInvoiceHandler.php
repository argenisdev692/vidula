<?php

declare(strict_types=1);

namespace Modules\Invoices\Application\Queries;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Invoices\Application\Support\InvoiceCacheKeys;
use Modules\Invoices\Domain\Ports\InvoiceRepositoryPort;
use Modules\Invoices\Infrastructure\Persistence\Eloquent\Models\InvoiceEloquentModel;

/**
 * Single-record lookup, cached 15 minutes per UUID (mirrors Meetings).
 * Mutating handlers forget {@see InvoiceCacheKeys::invoice()}.
 */
final readonly class GetInvoiceHandler
{
    public function __construct(
        private InvoiceRepositoryPort $invoices,
        private Cache $cache,
    ) {}

    public function handle(string $uuid): InvoiceEloquentModel
    {
        return $this->cache->remember(
            InvoiceCacheKeys::invoice($uuid),
            now()->addMinutes(15),
            fn () => $this->invoices->findByUuid($uuid)
                ?? throw (new ModelNotFoundException)->setModel(InvoiceEloquentModel::class, [$uuid]),
        );
    }
}
