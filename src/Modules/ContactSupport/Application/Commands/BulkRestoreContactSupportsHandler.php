<?php

declare(strict_types=1);

namespace Modules\ContactSupport\Application\Commands;

use Modules\ContactSupport\Domain\Ports\ContactSupportRepositoryPort;
use Shared\Application\DTOs\BulkUuidsData;

/**
 * Restores a set of soft-deleted contact-support submissions by UUID.
 * Authorization (permission:BULK_RESTORE_CONTACT_SUPPORTS) is enforced at the
 * route.
 */
final readonly class BulkRestoreContactSupportsHandler
{
    public function __construct(private ContactSupportRepositoryPort $contactSupports) {}

    public function handle(BulkUuidsData $data): int
    {
        return $this->contactSupports->bulkRestoreByUuid($data->uuids);
    }
}
