<?php

declare(strict_types=1);

namespace Modules\CompanyData\Application\Commands\CreateCompanyData;

use Illuminate\Support\Str;
use Modules\CompanyData\Domain\Entities\CompanyData;
use Modules\CompanyData\Domain\Enums\CompanyStatus;
use Modules\CompanyData\Domain\Ports\CompanyDataRepositoryPort;
use Modules\CompanyData\Domain\ValueObjects\CompanyDataId;
use Modules\CompanyData\Domain\ValueObjects\UserId;

/**
 * CreateCompanyDataHandler
 */
final readonly class CreateCompanyDataHandler
{
    public function __construct(
        private CompanyDataRepositoryPort $repository
    ) {
    }

    public function handle(CreateCompanyDataCommand $command): void
    {
        $dto = $command->dto;
        $uuid = Str::uuid()->toString();

        $companyData = CompanyData::create(
            id: new CompanyDataId($uuid),
            userId: new UserId($dto->userUuid),
            companyName: $dto->companyName,
            email: $dto->email,
            phone: $dto->phone,
            address: $dto->address,
            status: CompanyStatus::Active
        );

        $this->repository->save($companyData);
    }
}
