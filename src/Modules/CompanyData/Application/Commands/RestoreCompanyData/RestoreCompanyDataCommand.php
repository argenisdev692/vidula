<?php

declare(strict_types=1);

namespace Modules\CompanyData\Application\Commands\RestoreCompanyData;

final readonly class RestoreCompanyDataCommand
{
    public function __construct(
        public string $id
    ) {
    }
}
