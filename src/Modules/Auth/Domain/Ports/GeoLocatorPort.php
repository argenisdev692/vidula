<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Ports;

/**
 * Resolves the ISO-3166 alpha-2 country code for an authentication attempt
 * (prompt §7 audit field). Implementations MUST be best-effort and side-effect
 * free: geolocation may never throw or block the authentication path, and
 * returns null whenever the country cannot be determined.
 */
interface GeoLocatorPort
{
    public function country(?string $ipAddress): ?string;
}
