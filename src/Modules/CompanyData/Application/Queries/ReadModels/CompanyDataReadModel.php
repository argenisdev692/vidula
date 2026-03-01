<?php

declare(strict_types=1);

namespace Modules\CompanyData\Application\Queries\ReadModels;

use Spatie\LaravelData\Data;

/**
 * CompanyDataReadModel
 */
final class CompanyDataReadModel extends Data
{
    public function __construct(
        public string $uuid,
        public string $userUuid,
        public string $companyName,
        public ?string $email,
        public ?string $phone,
        public ?string $address,
        public array $socialLinks,
        public array $coordinates,
        public string $status,
        public ?string $signatureUrl = null,
    ) {
    }
}
