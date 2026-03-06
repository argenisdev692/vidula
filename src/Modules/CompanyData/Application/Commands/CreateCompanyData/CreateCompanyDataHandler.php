<?php

declare(strict_types=1);

namespace Modules\CompanyData\Application\Commands\CreateCompanyData;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\CompanyData\Domain\Entities\CompanyData;
use Modules\CompanyData\Domain\Enums\CompanyStatus;
use Modules\CompanyData\Domain\Ports\CompanyDataRepositoryPort;
use Modules\CompanyData\Domain\ValueObjects\CompanyDataId;
use Modules\CompanyData\Domain\ValueObjects\Coordinates;
use Modules\CompanyData\Domain\ValueObjects\SocialLinks;
use Modules\CompanyData\Domain\ValueObjects\UserId;
use Shared\Infrastructure\Audit\AuditInterface;
use Illuminate\Validation\ValidationException;

/**
 * CreateCompanyDataHandler
 */
final readonly class CreateCompanyDataHandler
{
    public function __construct(
        private CompanyDataRepositoryPort $repository,
        private AuditInterface $audit,
    ) {
    }

    public function handle(CreateCompanyDataCommand $command): void
    {
        if ($this->repository->existsAny()) {
            throw ValidationException::withMessages([
                'company_name' => 'Solo se permite registrar una empresa en el sistema.',
            ]);
        }

        $dto = $command->dto;
        $uuid = Str::uuid()->toString();

        $companyData = CompanyData::create(
            id: new CompanyDataId($uuid),
            userId: new UserId($dto->userUuid),
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
            status: CompanyStatus::Active,
        );

        $this->repository->save($companyData);

        // Audit business action
        $this->audit->log(
            logName: 'company.company_data',
            description: 'company_data.created',
            properties: ['uuid' => $uuid, 'company_name' => $dto->companyName],
        );

        // Invalidate list caches
        try {
            Cache::tags(['company_data'])->flush();
            Cache::tags(['company_data_list'])->flush();
        } catch (\Exception) {
            // Tags not supported — expires naturally
        }
    }
}
