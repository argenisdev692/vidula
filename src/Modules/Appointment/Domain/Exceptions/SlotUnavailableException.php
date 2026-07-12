<?php

declare(strict_types=1);

namespace Modules\Appointment\Domain\Exceptions;

use Modules\Appointment\Domain\Services\AppointmentScheduler;

/**
 * Raised by {@see AppointmentScheduler}
 * when the requested date/time falls outside every open availability window for
 * that day, or collides with another already-scheduled appointment.
 */
final class SlotUnavailableException extends \DomainException {}
