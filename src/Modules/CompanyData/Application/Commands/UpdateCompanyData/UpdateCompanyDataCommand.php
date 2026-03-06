<?php

declare(strict_types=1);

namespace Modules\CompanyData\Application\Commands\UpdateCompanyData;

use Modules\CompanyData\Application\DTOs\UpdateCompanyDataDTO;

final readonly class UpdateCompanyDataCommand
{
    public function __construct(
        public string $companyUuid,
        public UpdateCompanyDataDTO $dto
    ) {
    }
}
