<?php

declare(strict_types=1);

namespace Modules\Portfolio\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Portfolio\Domain\Ports\PortfolioRepositoryPort;
use Modules\Portfolio\Infrastructure\Cache\PortfolioPublicFeedCache;

/**
 * Restores a soft-deleted portfolio by UUID. Authorization
 * (permission:RESTORE_PORTFOLIOS) is enforced at the route.
 */
final readonly class RestorePortfolioHandler
{
    public function __construct(private PortfolioRepositoryPort $portfolios) {}

    public function handle(string $uuid): bool
    {
        $restored = DB::transaction(fn () => $this->portfolios->restore($uuid));

        PortfolioPublicFeedCache::flush();

        return $restored;
    }
}
