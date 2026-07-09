<?php

declare(strict_types=1);

namespace Modules\ContactSupport\Application\Commands;

use Modules\ContactSupport\Domain\Ports\ContactSupportRepositoryPort;
use Shared\Application\DTOs\BulkUuidsData;

/**
 * Soft-deletes a set of contact-support submissions by UUID. Authorization
 * (permission:BULK_DELETE_CONTACT_SUPPORTS) is enforced at the route.
 */
final readonly class BulkDeleteContactSupportsHandler
{
    public function __construct(private ContactSupportRepositoryPort $contactSupports) {}

    public function handle(BulkUuidsData $data): int
    {
        return $this->contactSupports->bulkSoftDeleteByUuid($data->uuids);
    }
}
