<?php

declare(strict_types=1);

namespace Modules\CompanyData\Application\Commands\CreateCompanyData;

use Modules\CompanyData\Application\DTOs\CreateCompanyDataDTO;

final readonly class CreateCompanyDataCommand
{
    public function __construct(
        public CreateCompanyDataDTO $dto
    ) {
    }
}
