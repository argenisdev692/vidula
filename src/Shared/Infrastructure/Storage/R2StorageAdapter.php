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

    /**
     * @return array{upload_url: string, headers: array<string, string>}
     */
    public function temporaryUploadUrl(string $path, \DateTimeInterface $expiresAt): array
    {
        /** @var array{url: string, headers: array<string, string>} $payload */
        $payload = $this->disk->temporaryUploadUrl($path, CarbonImmutable::instance($expiresAt));

        return [
            'upload_url' => $payload['url'],
            'headers' => $this->browserSafeUploadHeaders($payload['headers'] ?? []),
        ];
    }

    /**
     * Flysystem/S3 includes hop-by-hop headers (Host, Content-Length, …) that
     * browsers refuse to set on XHR. Only forward headers the browser may send
     * for a direct PUT to a presigned R2 URL.
     *
     * @param  array<string, string>  $headers
     * @return array<string, string>
     */
    private function browserSafeUploadHeaders(array $headers): array
    {
        $safe = [];

        foreach ($headers as $name => $value) {
            $lower = strtolower((string) $name);

            if ($lower === 'content-type' || str_starts_with($lower, 'x-amz-')) {
                $safe[(string) $name] = (string) $value;
            }
        }

        return $safe;
    }

    public function publicUrl(string $path): string
    {
        return $this->disk->url($path);
    }

    public function copyToLocal(string $path, string $localPath): void
    {
        $stream = $this->disk->readStream($path);
        if ($stream === null) {
            throw new \RuntimeException("Failed to open storage object [{$path}].");
        }

        try {
            $directory = dirname($localPath);
            if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
                throw new \RuntimeException("Failed to create local directory [{$directory}].");
            }

            $out = fopen($localPath, 'wb');
            if ($out === false) {
                throw new \RuntimeException("Failed to open local path [{$localPath}].");
            }

            try {
                $copied = stream_copy_to_stream($stream, $out);
                if ($copied === false) {
                    throw new \RuntimeException("Failed to copy storage object [{$path}] to local disk.");
                }
            } finally {
                fclose($out);
            }
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
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
