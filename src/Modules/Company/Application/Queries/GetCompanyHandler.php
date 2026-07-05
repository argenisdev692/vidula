<?php

declare(strict_types=1);

namespace Modules\Company\Application\Queries;

use App\Models\CompanyData;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Company\Domain\Ports\CompanyRepositoryPort;

final readonly class GetCompanyHandler
{
    public function __construct(private CompanyRepositoryPort $companies) {}

    public function handle(string $uuid): CompanyData
    {
        return $this->companies->findByUuid($uuid)
            ?? throw (new ModelNotFoundException)->setModel(CompanyData::class, [$uuid]);
    }
}
