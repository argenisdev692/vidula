<?php

declare(strict_types=1);

namespace Modules\ContactSupport\Application\Commands;

use Illuminate\Support\Facades\DB;
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
        return DB::transaction(fn () => $this->contactSupports->restore($uuid));
    }
}
