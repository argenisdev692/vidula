<?php

declare(strict_types=1);

namespace Modules\Appointment\Infrastructure\Availability;

use Carbon\CarbonInterface;
use Modules\Appointment\Domain\Ports\AvailabilityPort;
use Modules\Availability\Domain\Services\AvailabilityResolver;
use Modules\Availability\Domain\ValueObjects\ResolvedDay;

/**
 * Binds the Appointment {@see AvailabilityPort} to the Availability module's
 * {@see AvailabilityResolver} domain service. The only place the two modules'
 * scheduling internals meet — the container resolves it, so the Appointment
 * Domain layer stays free of a concrete cross-module service dependency.
 */
final readonly class AvailabilityResolverAdapter implements AvailabilityPort
{
    public function __construct(private AvailabilityResolver $resolver) {}

    public function resolve(CarbonInterface $date): ResolvedDay
    {
        return $this->resolver->resolve($date);
    }
}
