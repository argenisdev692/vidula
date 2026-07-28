<?php

declare(strict_types=1);

namespace Modules\Students\Application\Support;

/**
 * Redis cache key helpers for the Student aggregate (Get-by-UUID).
 */
final readonly class StudentCacheKeys
{
    public static function student(string $uuid): string
    {
        return "student_{$uuid}";
    }
}
