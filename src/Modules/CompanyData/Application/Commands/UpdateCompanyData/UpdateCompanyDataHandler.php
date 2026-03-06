<?php

declare(strict_types=1);

namespace Modules\CompanyData\Application\Commands\UpdateCompanyData;

use Illuminate\Support\Facades\Cache;
use Modules\CompanyData\Domain\Events\CompanyDataUpdated;
use Modules\CompanyData\Domain\Exceptions\CompanyDataNotFoundException;
use Modules\CompanyData\Domain\Ports\CompanyDataRepositoryPort;
use Modules\CompanyData\Domain\ValueObjects\CompanyDataId;
use Modules\CompanyData\Domain\ValueObjects\Coordinates;
use Modules\CompanyData\Domain\ValueObjects\SocialLinks;
use Shared\Domain\Events\DomainEventPublisher;
use Shared\Infrastructure\Audit\AuditInterface;

final readonly class UpdateCompanyDataHandler
{
    public function __construct(
        private CompanyDataRepositoryPort $repository,
        private AuditInterface $audit,
    ) {
    }

    public function handle(UpdateCompanyDataCommand $command): void
    {
        $companyId = new CompanyDataId($command->companyUuid);
        $companyData = $this->repository->findById($companyId);

        if (null === $companyData) {
            throw CompanyDataNotFoundException::forId($command->companyUuid);
        }

        $dto = $command->dto;

        $updatedCompanyData = $companyData->update(
            companyName: $dto->companyName,
            name: $dto->name,
            email: $dto->email,
            phone: $dto->phone,
            address: $dto->address,
            socialLinks: new SocialLinks(
                facebook: $dto->facebookLink,
                instagram: $dto->instagramLink,
                linkedin: $dto->linkedinLink,
                twitter: $dto->twitterLink,
                website: $dto->website,
            ),
            coordinates: new Coordinates(
                latitude: $dto->latitude,
                longitude: $dto->longitude,
            ),
            signaturePath: $dto->signaturePath,
        );

        $this->repository->save($updatedCompanyData);

        DomainEventPublisher::instance()->publish(
            new CompanyDataUpdated(
                aggregateId: $companyData->id->value,
                companyName: $dto->companyName,
                occurredOn: now()->toDateTimeString(),
            ),
        );

        // Audit business action
        $this->audit->log(
            logName: 'company.company_data',
            description: 'company_data.updated',
            properties: ['uuid' => $companyData->id->value, 'company_name' => $dto->companyName],
        );

        // Invalidate caches
        Cache::forget("company_data_company_{$companyData->id->value}");
        Cache::forget("company_data_user_{$companyData->userId->value}");
        Cache::forget("company_data_{$companyData->userId->value}");
        try {
            Cache::tags(['company_data'])->flush();
            Cache::tags(['company_data_list'])->flush();
        } catch (\Exception) {
            // Tags not supported — expires naturally
        }
    }
}
