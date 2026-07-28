<?php

declare(strict_types=1);

namespace Modules\Post\Domain\Ports;

/**
 * Invalidates the anonymous public post feed / detail cache after mutations.
 * Application handlers depend on this port; Infrastructure owns the Redis-tag
 * flush implementation (BACKEND-PHP §5 Cache Management).
 */
interface PostPublicFeedCachePort
{
    public function flush(): void;
}
