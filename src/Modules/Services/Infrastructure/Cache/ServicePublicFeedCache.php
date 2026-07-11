<?php

declare(strict_types=1);

namespace Modules\Services\Infrastructure\Cache;

use Illuminate\Support\Facades\Cache;
use Modules\Services\Application\Queries\ListPublicServicesHandler;
use Throwable;

/**
 * Invalidation for the public services feed cache (see
 * {@see ListPublicServicesHandler}).
 * Called by every Command handler that mutates a service, so the landing-page
 * select input never serves stale data past a save.
 *
 * Tag-based flush degrades silently when the cache store doesn't support tags
 * (BACKEND-PHP §5 Cache Management) — the entry still expires via its TTL.
 */
final class ServicePublicFeedCache
{
    public static function flush(): void
    {
        try {
            Cache::tags(['services_public'])->flush();
        } catch (Throwable) {
            // Store doesn't support tags — the cached list still expires via TTL.
        }
    }
}
