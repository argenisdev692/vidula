<?php

declare(strict_types=1);

namespace Modules\Appointment\Domain\ValueObjects;

use Modules\Appointment\Application\Commands\AddFollowUpCallHandler;

/**
 * Sales pipeline stage for a captured lead. `New` is the default on capture;
 * {@see AddFollowUpCallHandler} auto
 * -advances a `New` lead to `Called` on the first logged call attempt.
 */
enum StatusLead: string
{
    case New = 'New';

    case Called = 'Called';

    case Pending = 'Pending';

    case Declined = 'Declined';
}
