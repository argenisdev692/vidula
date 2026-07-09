<?php

declare(strict_types=1);

namespace Modules\ContactSupport\Application\Commands;

use Modules\ContactSupport\Domain\Ports\ContactSupportRepositoryPort;

/**
 * Marks a single submission as read (`readed = true`). Idempotent: re-running on
 * an already-read row is a no-op change. The state transition flows through a
 * model save so it is captured by the activity log. Authorization
 * (permission:UPDATE_CONTACT_SUPPORTS) is enforced at the route.
 */
final readonly class MarkContactSupportReadHandler
{
    public function __construct(private ContactSupportRepositoryPort $contactSupports) {}

    public function handle(string $uuid): bool
    {
        return $this->contactSupports->markAsRead($uuid);
    }
}
