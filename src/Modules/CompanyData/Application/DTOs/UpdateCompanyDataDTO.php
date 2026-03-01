<?php

declare(strict_types=1);

namespace Modules\CompanyData\Application\DTOs;

use Spatie\LaravelData\Data;

/**
 * UpdateCompanyDataDTO
 */
final class UpdateCompanyDataDTO extends Data
{
    public function __construct(
        public string $companyName,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $address = null,
        public ?string $website = null,
        public ?string $facebook = null,
        public ?string $instagram = null,
        public ?string $linkedin = null,
        public ?string $twitter = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
    ) {
    }
}
