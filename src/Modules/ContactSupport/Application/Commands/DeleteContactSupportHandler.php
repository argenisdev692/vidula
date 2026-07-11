<?php

declare(strict_types=1);

namespace Modules\ContactSupport\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\ContactSupport\Domain\Ports\ContactSupportRepositoryPort;

/**
 * Soft-deletes a single contact-support submission by UUID. The row is kept as a
 * tombstone so a restore is lossless. Authorization
 * (permission:DELETE_CONTACT_SUPPORTS) is enforced at the route.
 */
final readonly class DeleteContactSupportHandler
{
    public function __construct(private ContactSupportRepositoryPort $contactSupports) {}

    public function handle(string $uuid): bool
    {
        return DB::transaction(fn () => $this->contactSupports->softDelete($uuid));
    }
}
