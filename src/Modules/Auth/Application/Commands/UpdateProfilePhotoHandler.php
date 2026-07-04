<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Commands;

use Illuminate\Http\UploadedFile;
use Shared\Domain\Ports\StoragePort;

/**
 * Stores a user's profile photo on the cloud disk (Cloudflare R2 via StoragePort)
 * and returns the stored path so the caller can persist it on the user record.
 *
 * Old-photo cleanup is atomic to the replace: once the new object is stored, the
 * previous one is deleted so R2 never accumulates orphaned avatars. Persistence
 * of the path column stays in the Infrastructure controller (Eloquent) — this
 * Application handler owns only the storage side-effect through the port.
 */
final readonly class UpdateProfilePhotoHandler
{
    public function __construct(private StoragePort $storage) {}

    #[\NoDiscard('The stored photo path must be persisted to the user record')]
    public function handle(string $userUuid, ?string $currentPath, UploadedFile $photo): string
    {
        $path = $this->storage->putFile("profile-photos/{$userUuid}", $photo);

        if ($currentPath !== null && $currentPath !== $path) {
            $this->storage->delete($currentPath);
        }

        return $path;
    }
}
