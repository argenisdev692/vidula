<?php

declare(strict_types=1);

namespace Modules\Clients\Application\Support;

/**
 * Redis cache key helpers for the Client aggregate (Get-by-UUID).
 */
final readonly class ClientCacheKeys
{
    public static function client(string $uuid): string
    {
        return "client_{$uuid}";
    }
}
