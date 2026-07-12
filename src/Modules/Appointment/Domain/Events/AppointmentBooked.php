<?php

declare(strict_types=1);

namespace Modules\Appointment\Domain\Events;

/**
 * Raised once, when a brand-new lead is captured (never on a resubmission that
 * only updates an existing active lead) — {@see SendNewLeadAdminEmailListener}
 * notifies the company inbox.
 */
final readonly class AppointmentBooked
{
    public function __construct(public string $uuid) {}
}
