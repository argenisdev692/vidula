<?php

declare(strict_types=1);

namespace Modules\Availability\Application\Commands;

use Modules\Availability\Domain\Ports\AvailabilityExceptionRepositoryPort;

/**
 * Soft-deletes a single date exception by UUID. Authorization
 * (permission:DELETE_AVAILABILITY_EXCEPTIONS) is enforced at the route.
 */
final readonly class DeleteAvailabilityExceptionHandler
{
    public function __construct(private AvailabilityExceptionRepositoryPort $exceptions) {}

    public function handle(string $uuid): bool
    {
        return $this->exceptions->softDelete($uuid);
    }
}
