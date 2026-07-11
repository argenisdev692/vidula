<?php

declare(strict_types=1);

namespace Modules\Portfolio\Application\Queries;

use Illuminate\Support\Facades\Cache;
use Modules\Portfolio\Application\ReadModels\PortfolioPublicReadModel;
use Modules\Portfolio\Domain\Ports\PortfolioRepositoryPort;
use Modules\Portfolio\Infrastructure\Cache\PortfolioPublicFeedCache;
use Spatie\LaravelData\PaginatedDataCollection;
use Throwable;

/**
 * Landing-page feed: `is_public` portfolios only, no authentication required.
 * Kept as its own query (rather than reusing {@see ListPortfoliosHandler})
 * since the trust boundary and column allowlist genuinely diverge — this one
 * is reachable by anonymous internet traffic (BACKEND-PHP §7 Insecure Design).
 *
 * Cached, unlike the admin `ListPortfoliosHandler`/`GetPortfolioHandler`: this
 * is the one query hit by anonymous traffic with no per-user throttle, so
 * caching earns its complexity here specifically (BACKEND-PHP §5 Cache
 * Management). Every Create/Update/Delete/Restore/Bulk/Gallery handler busts
 * the `portfolios_public` tag via {@see PortfolioPublicFeedCache}.
 */
final readonly class ListPublicPortfoliosHandler
{
    private const int TTL_MINUTES = 5;

    public function __construct(private PortfolioRepositoryPort $portfolios) {}

    /**
     * @return PaginatedDataCollection<int, PortfolioPublicReadModel>
     */
    public function handle(int $perPage = 15): PaginatedDataCollection
    {
        $page = max((int) request()->integer('page', 1), 1);
        $cacheKey = "portfolios.public.page.{$page}.per_page.{$perPage}";

        try {
            $paginator = Cache::tags(['portfolios_public'])->remember(
                $cacheKey,
                now()->addMinutes(self::TTL_MINUTES),
                fn () => $this->portfolios->paginatePublic($perPage),
            );
        } catch (Throwable) {
            $paginator = Cache::remember(
                $cacheKey,
                now()->addMinutes(self::TTL_MINUTES),
                fn () => $this->portfolios->paginatePublic($perPage),
            );
        }

        // Map to the public allowlist AFTER the cache boundary: the cache keeps
        // storing the Eloquent paginator (unchanged, proven behaviour) while the
        // response shape is now a precise, id-free ReadModel collection.
        return PortfolioPublicReadModel::collect($paginator, PaginatedDataCollection::class);
    }
}
