<?php

declare(strict_types=1);

namespace Modules\Users\Infrastructure\ExternalServices\Storage;

use Modules\Users\Domain\Ports\StoragePort;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * SpatieMediaLibraryStorageAdapter — Implements StoragePort using Spatie Media Library.
 */
final class SpatieMediaLibraryStorageAdapter implements StoragePort
{
    /**
     * Store a file and return its path
     */
    public function upload(UploadedFile $file): string
    {
        // Store file using Laravel's storage
        $storedPath = $file->store('avatars', 'public');

        return $storedPath;
    }

    /**
     * Delete a file by path
     */
    public function delete(string $path): void
    {
        \Storage::disk('public')->delete($path);
    }

    /**
     * Get public URL for a file
     */
    public function getUrl(string $path): string
    {
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = \Storage::disk('public');
        return $disk->url($path);
    }

    /**
     * Check if file exists
     */
    public function exists(string $path): bool
    {
        return \Storage::disk('public')->exists($path);
    }

    /**
     * Store file using Spatie Media Library (for models that use HasMedia trait)
     */
    public function storeAsMedia($model, UploadedFile $file, string $collection = 'default'): Media
    {
        return $model
            ->addMedia($file)
            ->toMediaCollection($collection);
    }

    /**
     * Delete media by ID
     */
    public function deleteMedia(int $mediaId): bool
    {
        /** @var Media|null $media */
        $media = Media::find($mediaId);

        if ($media) {
            $media->delete();
            return true;
        }

        return false;
    }
}

