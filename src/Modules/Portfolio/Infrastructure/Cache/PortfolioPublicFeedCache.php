<?php

declare(strict_types=1);

namespace Modules\Portfolio\Infrastructure\Cache;

use Illuminate\Support\Facades\Cache;
use Modules\Portfolio\Application\Queries\ListPublicPortfoliosHandler;
use Throwable;

/**
 * Invalidation for the public portfolio feed cache (see
 * {@see ListPublicPortfoliosHandler}).
 * Called by every Command handler that mutates a portfolio or its gallery, so
 * the anonymous landing-page feed never serves stale data past a save.
 *
 * Tag-based flush degrades silently when the cache store doesn't support tags
 * (BACKEND-PHP §5 Cache Management) — entries still expire via their TTL.
 */
final class PortfolioPublicFeedCache
{
    public static function flush(): void
    {
        try {
            Cache::tags(['portfolios_public'])->flush();
        } catch (Throwable) {
            // Store doesn't support tags — cached pages still expire via TTL.
        }
    }
}
