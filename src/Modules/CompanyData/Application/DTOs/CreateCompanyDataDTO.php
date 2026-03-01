<?php

declare(strict_types=1);

namespace Modules\CompanyData\Application\DTOs;

use Spatie\LaravelData\Data;

/**
 * CreateCompanyDataDTO
 */
final class CreateCompanyDataDTO extends Data
{
    public function __construct(
        public string $userUuid,
        public string $companyName,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $address = null,
    ) {
    }
}
