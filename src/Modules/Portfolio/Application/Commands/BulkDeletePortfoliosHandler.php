<?php

declare(strict_types=1);

namespace Modules\Portfolio\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Portfolio\Domain\Ports\PortfolioRepositoryPort;
use Modules\Portfolio\Infrastructure\Cache\PortfolioPublicFeedCache;
use Shared\Application\DTOs\BulkUuidsData;

/**
 * Soft-deletes a set of portfolios by UUID. Authorization
 * (permission:BULK_DELETE_PORTFOLIOS) is enforced at the route — never inside
 * this handler (no god-handler).
 */
final readonly class BulkDeletePortfoliosHandler
{
    public function __construct(private PortfolioRepositoryPort $portfolios) {}

    public function handle(BulkUuidsData $data): int
    {
        $count = DB::transaction(fn () => $this->portfolios->bulkSoftDeleteByUuid($data->uuids));

        PortfolioPublicFeedCache::flush();

        return $count;
    }
}
