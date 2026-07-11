<?php

declare(strict_types=1);

namespace Modules\Portfolio\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Portfolio\Domain\Ports\PortfolioRepositoryPort;
use Modules\Portfolio\Infrastructure\Cache\PortfolioPublicFeedCache;
use Shared\Application\DTOs\BulkUuidsData;

/**
 * Restores a set of soft-deleted portfolios by UUID. Authorization
 * (permission:BULK_RESTORE_PORTFOLIOS) is enforced at the route.
 */
final readonly class BulkRestorePortfoliosHandler
{
    public function __construct(private PortfolioRepositoryPort $portfolios) {}

    public function handle(BulkUuidsData $data): int
    {
        $count = DB::transaction(fn () => $this->portfolios->bulkRestoreByUuid($data->uuids));

        PortfolioPublicFeedCache::flush();

        return $count;
    }
}
