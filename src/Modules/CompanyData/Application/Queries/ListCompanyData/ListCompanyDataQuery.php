<?php

declare(strict_types=1);

namespace Modules\CompanyData\Application\Queries\ListCompanyData;

use Modules\CompanyData\Application\DTOs\CompanyDataFilterDTO;

final readonly class ListCompanyDataQuery
{
    public function __construct(
        public CompanyDataFilterDTO $filters
    ) {
    }
}
