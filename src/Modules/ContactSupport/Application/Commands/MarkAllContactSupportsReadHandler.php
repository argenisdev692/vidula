<?php

declare(strict_types=1);

namespace Modules\ContactSupport\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\ContactSupport\Domain\Ports\ContactSupportRepositoryPort;

/**
 * Marks every unread submission as read in one shot — the navbar bell's
 * "mark all as read" action. Authorization (permission:UPDATE_CONTACT_SUPPORTS)
 * is enforced at the route.
 */
final readonly class MarkAllContactSupportsReadHandler
{
    public function __construct(private ContactSupportRepositoryPort $contactSupports) {}

    public function handle(): int
    {
        return DB::transaction(fn () => $this->contactSupports->markAllAsRead());
    }
}
