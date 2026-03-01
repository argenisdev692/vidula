<?php

declare(strict_types=1);

namespace Modules\CompanyData\Application\Commands\UpdateCompanyData;

use Modules\CompanyData\Domain\Exceptions\CompanyDataNotFoundException;
use Modules\CompanyData\Domain\Ports\CompanyDataRepositoryPort;
use Modules\CompanyData\Domain\ValueObjects\Coordinates;
use Modules\CompanyData\Domain\ValueObjects\SocialLinks;
use Modules\CompanyData\Domain\ValueObjects\UserId;
use Modules\CompanyData\Domain\Events\CompanyDataUpdated;
use Shared\Domain\Events\DomainEventPublisher;

final readonly class UpdateCompanyDataHandler
{
    public function __construct(
        private CompanyDataRepositoryPort $repository
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
                website: $dto->website
            ),
            coordinates: new Coordinates(
                latitude: $dto->latitude,
                longitude: $dto->longitude
            )
        );

        $this->repository->save($updatedCompanyData);

        DomainEventPublisher::instance()->publish(
            new CompanyDataUpdated(
                aggregateId: $companyData->id->value,
                companyName: $dto->companyName,
                occurredOn: now()->toDateTimeString()
            )
        );
    }
}
