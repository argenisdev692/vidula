<?php

declare(strict_types=1);

namespace Modules\Portfolio\Application\Support;

use Shared\Domain\Ports\StoragePort;

/**
 * Ensures client-supplied R2 keys for portfolio cover/video are under the
 * expected prefixes, have no path traversal, and already exist on storage
 * (object was uploaded via a prior presign PUT).
 */
final readonly class PortfolioMediaKeyGuard
{
    public function __construct(
        private StoragePort $storage,
    ) {}

    public function assertValidCoverKey(string $key): void
    {
        $this->assertValidKey($key, (string) config('portfolio.cover_prefix', 'portfolios/cover'), 'cover');
    }

    public function assertValidVideoKey(string $key): void
    {
        $this->assertValidKey($key, (string) config('portfolio.video_prefix', 'portfolios/video'), 'video');
    }

    private function assertValidKey(string $key, string $prefix, string $label): void
    {
        $normalized = ltrim($key, '/');
        $expected = rtrim($prefix, '/').'/';

        if (
            $normalized === ''
            || str_contains($normalized, '..')
            || ! str_starts_with($normalized, $expected)
        ) {
            throw new \InvalidArgumentException("Invalid portfolio {$label} storage key.");
        }

        if (! $this->storage->exists($normalized)) {
            throw new \InvalidArgumentException("Portfolio {$label} object was not found in storage.");
        }
    }
}
