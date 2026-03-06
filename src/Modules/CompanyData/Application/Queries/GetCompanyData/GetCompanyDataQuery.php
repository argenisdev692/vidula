<?php

declare(strict_types=1);

namespace Modules\CompanyData\Application\Queries\GetCompanyData;

final readonly class GetCompanyDataQuery
{
    public function __construct(
        public ?string $companyUuid = null,
        public ?string $userUuid = null,
    ) {
    }
}
