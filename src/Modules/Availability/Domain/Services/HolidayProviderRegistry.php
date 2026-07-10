<?php

declare(strict_types=1);

namespace Modules\Availability\Domain\Services;

final class HolidayProviderRegistry
{
    private const string DEFAULT_COUNTRY = 'PT';

    /**
     * @var array<string, HolidayProvider>
     */
    private array $providers = [];

    /**
     * @param  iterable<HolidayProvider>  $providers
     */
    public function __construct(iterable $providers)
    {
        foreach ($providers as $provider) {
            $this->providers[strtoupper($provider->countryCode())] = $provider;
        }
    }

    public function forCountry(?string $countryCode): HolidayProvider
    {
        $code = strtoupper((string) ($countryCode ?? self::DEFAULT_COUNTRY));

        return $this->providers[$code]
            ?? $this->providers[self::DEFAULT_COUNTRY];
    }
}
