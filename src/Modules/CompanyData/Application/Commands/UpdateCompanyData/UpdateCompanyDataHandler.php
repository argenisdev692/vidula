<?php

declare(strict_types=1);

namespace Modules\CompanyData\Application\Commands\UpdateCompanyData;

use Illuminate\Support\Facades\Cache;
use Modules\CompanyData\Domain\Events\CompanyDataUpdated;
use Modules\CompanyData\Domain\Exceptions\CompanyDataNotFoundException;
use Modules\CompanyData\Domain\Ports\CompanyDataRepositoryPort;
use Modules\CompanyData\Domain\ValueObjects\Coordinates;
use Modules\CompanyData\Domain\ValueObjects\SocialLinks;
use Modules\CompanyData\Domain\ValueObjects\UserId;
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
        $userId = new UserId($command->userUuid);
        $companyData = $this->repository->findByUserId($userId);

        if (null === $companyData) {
            throw CompanyDataNotFoundException::forUser($command->userUuid);
        }

        $dto = $command->dto;

        $updatedCompanyData = $companyData->update(
            companyName: $dto->companyName,
            email: $dto->email,
            phone: $dto->phone,
            address: $dto->address,
            socialLinks: new SocialLinks(
                facebook: $dto->facebook,
                instagram: $dto->instagram,
                linkedin: $dto->linkedin,
                twitter: $dto->twitter,
                website: $dto->website,
            ),
            coordinates: new Coordinates(
                latitude: $dto->latitude,
                longitude: $dto->longitude,
            ),
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
        Cache::forget("company_data_{$command->userUuid}");
        try {
            Cache::tags(['company_data_list'])->flush();
        } catch (\Exception) {
            // Tags not supported — expires naturally
        }
    }
}
