<?php

declare(strict_types=1);

namespace Modules\Users\Infrastructure\ExternalServices\Storage;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Users\Domain\Ports\StoragePort;

/**
 * AvatarStorageAdapter
 */
final class AvatarStorageAdapter implements StoragePort
{
    private const AVATARS_DISK = 'public';
    private const AVATARS_DIR = 'avatars';

    public function upload(UploadedFile $file): string
    {
        $path = $file->store(self::AVATARS_DIR, self::AVATARS_DISK);

        if (!$path) {
            throw new \RuntimeException("Failed to upload avatar.");
        }

        return $path;
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
