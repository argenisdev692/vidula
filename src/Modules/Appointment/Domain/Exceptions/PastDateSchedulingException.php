<?php

declare(strict_types=1);

namespace Modules\Appointment\Domain\Exceptions;

use Modules\Appointment\Domain\Services\AppointmentScheduler;

/**
 * Raised by {@see AppointmentScheduler}
 * when the requested date/time is not strictly in the future. Framework-free by
 * design (Domain layer); the Application handler that catches it converts it to
 * an `Illuminate\Validation\ValidationException` (422 / redirect-back-with-errors).
 */
final class PastDateSchedulingException extends \DomainException {}
