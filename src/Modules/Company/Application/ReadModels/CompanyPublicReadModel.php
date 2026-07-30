<?php

declare(strict_types=1);

namespace Modules\Company\Application\ReadModels;

use Shared\Infrastructure\Company\CompanyProfile;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Landing-page company payload (Astro). Property-level allowlist (OWASP §12):
 * marketing/contact fields only — never bank details, NIF/NIE, invoice notes,
 * signature paths, or internal `id` / `user_id`.
 *
 * Built from {@see CompanyProfile::data()} so logo URL resolution + Redis cache
 * stay in one place (bust via CompanyData saved/deleted hooks).
 */
#[MapOutputName(SnakeCaseMapper::class)]
final class CompanyPublicReadModel extends Data
{
    /**
     * @param  array<string, string>  $socials
     */
    public function __construct(
        public string $name,
        public ?string $description,
        public ?string $website,
        public string $logoUrl,
        public string $logoWhiteUrl,
        public string $markUrl,
        public ?string $email,
        public ?string $phone,
        public ?string $address,
        // Underscore property so SnakeCaseMapper emits `address_2` (Str::snake('address2') would stay `address2`).
        public ?string $address_2,
        public ?string $zipCode,
        public ?string $city,
        public ?string $state,
        public ?string $country,
        public ?string $countryCode,
        public ?float $latitude,
        public ?float $longitude,
        public array $socials,
    ) {}

    public static function fromProfile(): self
    {
        /** @var array{name: string, description: ?string, url: ?string, logo_url: string, logo_white_url: string, mark_url: string, address: ?string, address_2: ?string, zip_code: ?string, city: ?string, state: ?string, country: ?string, country_code: ?string, phone: ?string, support_email: ?string, latitude: ?float, longitude: ?float, socials: array<string, string>} $profile */
        $profile = CompanyProfile::data();

        return new self(
            name: $profile['name'],
            description: $profile['description'],
            website: $profile['url'],
            logoUrl: $profile['logo_url'],
            logoWhiteUrl: $profile['logo_white_url'],
            markUrl: $profile['mark_url'],
            email: $profile['support_email'],
            phone: $profile['phone'],
            address: $profile['address'],
            address_2: $profile['address_2'],
            zipCode: $profile['zip_code'],
            city: $profile['city'],
            state: $profile['state'],
            country: $profile['country'],
            countryCode: $profile['country_code'],
            latitude: $profile['latitude'],
            longitude: $profile['longitude'],
            socials: $profile['socials'],
        );
    }
}
