<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\CompanyData\Application\Commands\CreateCompanyData\CreateCompanyDataCommand;
use Modules\CompanyData\Application\Commands\CreateCompanyData\CreateCompanyDataHandler;
use Modules\CompanyData\Application\DTOs\CreateCompanyDataDTO;
use Modules\CompanyData\Domain\Ports\CompanyDataRepositoryPort;
use Shared\Infrastructure\Audit\AuditInterface;
use Mockery\MockInterface;

it('creates company data and records audit', function (): void {
    /** @var CompanyDataRepositoryPort&MockInterface $repository */
    $repository = Mockery::mock(CompanyDataRepositoryPort::class);
    $repository->shouldReceive('existsAny')->once()->andReturn(false);
    $repository->shouldReceive('save')->once();

    /** @var AuditInterface&MockInterface $audit */
    $audit = Mockery::mock(AuditInterface::class);
    $audit->shouldReceive('log')
        ->once()
        ->with('company.company_data', 'company_data.created', Mockery::type('array'));

    $handler = new CreateCompanyDataHandler($repository, $audit);

    $userUuid = Str::uuid()->toString();
    $dto = new CreateCompanyDataDTO(
        userUuid: $userUuid,
        companyName: 'Test Corp',
        email: 'test@corp.com',
    );

    $command = new CreateCompanyDataCommand($dto);

    $handler->handle($command);
});
