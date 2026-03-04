<?php

declare(strict_types=1);

namespace Modules\Users\Infrastructure\ExternalServices\Storage;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Users\Domain\Ports\StoragePort;

/**
 * AvatarStorageAdapter — Infrastructure adapter for avatar file storage.
 */
final class AvatarStorageAdapter implements StoragePort
{
    private const AVATARS_DISK = 'public';
    private const AVATARS_DIR = 'avatars';

    /**
     * @param resource $stream   Readable stream resource
     * @param string   $filename Original filename (for extension detection)
     */
    public function upload(mixed $stream, string $filename): string
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION) ?: 'jpg';
        $storedName = self::AVATARS_DIR . '/' . Str::uuid()->toString() . '.' . $extension;

        $result = Storage::disk(self::AVATARS_DISK)->put($storedName, $stream);

        if (!$result) {
            throw new \RuntimeException("Failed to upload avatar.");
        }

        return $storedName;
    }

    public function delete(string $path): void
    {
        if (Storage::disk(self::AVATARS_DISK)->exists($path)) {
            Storage::disk(self::AVATARS_DISK)->delete($path);
        }
    }

    public function getUrl(string $path): string
    {
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk(self::AVATARS_DISK);

        return $disk->url($path);
    }
}
