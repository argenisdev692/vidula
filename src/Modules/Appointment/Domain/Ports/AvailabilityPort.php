<?php

declare(strict_types=1);

namespace Modules\Appointment\Domain\Ports;

use Carbon\CarbonInterface;
use Modules\Availability\Domain\Services\AvailabilityResolver;
use Modules\Availability\Domain\ValueObjects\ResolvedDay;

/**
 * Anti-corruption boundary onto the Availability bounded context. The scheduler
 * depends on THIS abstraction (owned by the Appointment Domain) instead of the
 * concrete {@see AvailabilityResolver},
 * so the cross-module wiring lives in Infrastructure — mirroring how the lead
 * listener reaches Company through its `CompanyRepositoryPort`.
 *
 * The returned {@see ResolvedDay} is treated as a shared read model (same way
 * the Company port returns `CompanyData`); Appointment never mutates it.
 */
interface AvailabilityPort
{
    public function resolve(CarbonInterface $date): ResolvedDay;
}
