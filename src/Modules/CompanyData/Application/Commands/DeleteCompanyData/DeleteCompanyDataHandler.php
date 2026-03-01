<?php

declare(strict_types=1);

namespace Modules\CompanyData\Application\Commands\DeleteCompanyData;

use Modules\CompanyData\Domain\Exceptions\CompanyDataNotFoundException;
use Modules\CompanyData\Domain\Ports\CompanyDataRepositoryPort;
use Modules\CompanyData\Domain\ValueObjects\CompanyDataId;
use Illuminate\Support\Facades\Cache;

final readonly class DeleteCompanyDataHandler
{
    public function __construct(
        private CompanyDataRepositoryPort $repository
    ) {
    }

    public function handle(DeleteCompanyDataCommand $command): void
    {
        $id = new CompanyDataId($command->id);
        $companyData = $this->repository->findById($id);

        if (null === $companyData) {
            throw CompanyDataNotFoundException::forId($command->id);
        }

        $this->repository->delete($id);

        // Clear caches
        Cache::forget("company_data_{$command->id}");
        Cache::forget("company_data_{$companyData->userId->value}");
    }
}
