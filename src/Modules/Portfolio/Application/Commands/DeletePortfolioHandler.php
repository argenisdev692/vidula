<?php

declare(strict_types=1);

namespace Modules\Portfolio\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Portfolio\Domain\Ports\PortfolioRepositoryPort;
use Modules\Portfolio\Infrastructure\Cache\PortfolioPublicFeedCache;

/**
 * Soft-deletes a single portfolio by UUID. Assets (cover/video/gallery) are
 * intentionally kept so a restore is lossless. Authorization
 * (permission:DELETE_PORTFOLIOS) is enforced at the route.
 */
final readonly class DeletePortfolioHandler
{
    public function __construct(private PortfolioRepositoryPort $portfolios) {}

    public function handle(string $uuid): bool
    {
        $deleted = DB::transaction(fn () => $this->portfolios->softDelete($uuid));

        PortfolioPublicFeedCache::flush();

        return $deleted;
    }
}
