<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Commands;

use Shared\Domain\Ports\StoragePort;

/**
 * Removes a user's profile photo from the cloud disk (Cloudflare R2). Nulling the
 * path column stays with the Infrastructure controller; this handler owns only the
 * storage deletion through the port. Safe no-op when the user has no photo.
 */
final readonly class DeleteProfilePhotoHandler
{
    public function __construct(private StoragePort $storage) {}

    public function handle(?string $currentPath): void
    {
        if ($currentPath !== null) {
            $this->storage->delete($currentPath);
        }
    }
}
