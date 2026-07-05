<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Storage;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Shared\Domain\Ports\StoragePort;

/**
 * StoragePort over the S3-compatible Cloudflare R2 disk.
 *
 * `local`/`public` disks are forbidden as the final destination for user/business
 * uploads (BACKEND-PHP §5). Defaults to the `r2` disk; injectable for tests.
 */
final readonly class R2StorageAdapter implements StoragePort
{
    private Filesystem $disk;

    public function __construct(?string $disk = null)
    {
        $this->disk = Storage::disk($disk ?? (string) config('filesystems.cloud', 'r2'));
    }

    public function put(string $path, string $contents, string $visibility = 'private'): string
    {
        $this->disk->put($path, $contents, ['visibility' => $visibility]);

        return $path;
    }

    public function putFile(string $directory, \SplFileInfo $file, string $visibility = 'private'): string
    {
        $path = $this->disk->putFile($directory, $file, ['visibility' => $visibility]);

        if ($path === false) {
            throw new \RuntimeException("Failed to store file in [{$directory}].");
        }

        return $path;
    }

    public function temporaryUrl(string $path, \DateTimeInterface $expiresAt): string
    {
        return $this->disk->temporaryUrl($path, CarbonImmutable::instance($expiresAt));
    }

    public function publicUrl(string $path): string
    {
        return $this->disk->url($path);
    }

    public function delete(string $path): bool
    {
        return $this->disk->delete($path);
    }

    public function exists(string $path): bool
    {
        return $this->disk->exists($path);
    }
}
