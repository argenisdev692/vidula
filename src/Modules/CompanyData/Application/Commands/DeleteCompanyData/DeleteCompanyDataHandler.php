<?php

declare(strict_types=1);

namespace Modules\CompanyData\Application\Commands\DeleteCompanyData;

use Illuminate\Support\Facades\Cache;
use Modules\CompanyData\Domain\Exceptions\CompanyDataNotFoundException;
use Modules\CompanyData\Domain\Ports\CompanyDataRepositoryPort;
use Modules\CompanyData\Domain\ValueObjects\CompanyDataId;
use Shared\Infrastructure\Audit\AuditInterface;

final readonly class DeleteCompanyDataHandler
{
    public function __construct(
        private CompanyDataRepositoryPort $repository,
        private AuditInterface $audit,
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

        // Audit business action
        $this->audit->log(
            logName: 'company.company_data',
            description: 'company_data.deleted',
            properties: ['uuid' => $command->id, 'company_name' => $companyData->companyName],
        );

        // Clear caches
        Cache::forget("company_data_{$command->id}");
        Cache::forget("company_data_{$companyData->userId->value}");
        try {
            Cache::tags(['company_data_list'])->flush();
        } catch (\Exception) {
            // Tags not supported — expires naturally
        }
    }
}
