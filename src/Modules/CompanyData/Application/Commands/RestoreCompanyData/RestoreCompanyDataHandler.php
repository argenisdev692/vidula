<?php

declare(strict_types=1);

namespace Modules\CompanyData\Application\Commands\RestoreCompanyData;

use Modules\CompanyData\Domain\Exceptions\CompanyDataNotFoundException;
use Modules\CompanyData\Domain\Ports\CompanyDataRepositoryPort;
use Modules\CompanyData\Domain\ValueObjects\CompanyDataId;

final readonly class RestoreCompanyDataHandler
{
    public function __construct(
        private CompanyDataRepositoryPort $repository
    ) {
    }

    public function handle(RestoreCompanyDataCommand $command): void
    {
        $id = new CompanyDataId($command->id);
        $this->repository->restore($id);
    }
}
