<?php

declare(strict_types=1);

namespace Modules\Appointment\Domain\Events;

final readonly class AppointmentRescheduled
{
    public function __construct(public string $uuid) {}
}
