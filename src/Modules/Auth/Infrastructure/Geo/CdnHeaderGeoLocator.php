<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Geo;

use Illuminate\Http\Request;
use Modules\Auth\Domain\Ports\GeoLocatorPort;

/**
 * Zero-dependency geolocation from the CDN edge country header (Cloudflare
 * `CF-IPCountry`, with common fallbacks). No external database or API call is
 * made on the authentication hot path.
 *
 * Swap this container binding for a MaxMind/GeoIP2 adapter — which would use
 * the `$ipAddress` argument — when edge headers are unavailable.
 */
final readonly class CdnHeaderGeoLocator implements GeoLocatorPort
{
    /** Edge headers carrying an ISO-3166 alpha-2 country, in priority order. */
    private const array COUNTRY_HEADERS = [
        'CF-IPCountry',           // Cloudflare
        'X-Vercel-IP-Country',    // Vercel
        'Fly-Client-IP-Country',  // Fly.io
        'X-Country-Code',         // generic / custom load balancer
    ];

    /** Cloudflare placeholders for unknown / Tor / reserved ranges. */
    private const array NON_COUNTRIES = ['XX', 'T1', 'A1', 'A2'];

    public function __construct(private Request $request) {}

    public function country(?string $ipAddress): ?string
    {
        foreach (self::COUNTRY_HEADERS as $header) {
            $value = $this->request->headers->get($header);

            if ($value === null) {
                continue;
            }

            $code = strtoupper(trim($value));

            if (preg_match('/^[A-Z]{2}$/', $code) === 1 && ! in_array($code, self::NON_COUNTRIES, true)) {
                return $code;
            }
        }

        return null;
    }
}
