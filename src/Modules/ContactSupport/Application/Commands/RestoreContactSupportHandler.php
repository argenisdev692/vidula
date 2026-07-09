<?php

declare(strict_types=1);

namespace Modules\ContactSupport\Application\Commands;

use Modules\ContactSupport\Domain\Ports\ContactSupportRepositoryPort;

/**
 * Restores a soft-deleted contact-support submission by UUID. Authorization
 * (permission:RESTORE_CONTACT_SUPPORTS) is enforced at the route.
 */
final readonly class RestoreContactSupportHandler
{
    public function __construct(private ContactSupportRepositoryPort $contactSupports) {}

    public function handle(string $uuid): bool
    {
        return $this->contactSupports->restore($uuid);
    }
}
