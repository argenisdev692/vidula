<?php

declare(strict_types=1);

namespace Modules\CompanyData\Domain\Ports;

use Modules\CompanyData\Domain\Entities\CompanyData;
use Modules\CompanyData\Domain\ValueObjects\CompanyDataId;
use Modules\CompanyData\Domain\ValueObjects\UserId;

/**
 * CompanyDataRepositoryPort
 */
interface CompanyDataRepositoryPort
{
    public function findById(CompanyDataId $id): ?CompanyData;

    public function findByUserId(UserId $userId): ?CompanyData;

    public function existsAny(): bool;

    public function save(CompanyData $companyData): void;

    public function delete(CompanyDataId $id): void;

    public function restore(CompanyDataId $id): void;

    /**
     * @param array<string, mixed> $filters
     */
    public function findAllPaginated(array $filters = [], int $page = 1, int $perPage = 15): array;
}
