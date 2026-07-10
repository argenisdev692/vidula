<?php

declare(strict_types=1);

namespace Modules\Availability\Application\Commands;

use Modules\Availability\Domain\Ports\AvailabilityExceptionRepositoryPort;

/**
 * Restores a soft-deleted date exception by UUID. Authorization
 * (permission:RESTORE_AVAILABILITY_EXCEPTIONS) is enforced at the route.
 */
final readonly class RestoreAvailabilityExceptionHandler
{
    public function __construct(private AvailabilityExceptionRepositoryPort $exceptions) {}

    public function handle(string $uuid): bool
    {
        return $this->exceptions->restore($uuid);
    }
}
