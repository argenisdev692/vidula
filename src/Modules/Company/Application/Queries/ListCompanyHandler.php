<?php

declare(strict_types=1);

namespace Modules\Company\Application\Queries;

use App\Models\CompanyData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Company\Domain\Ports\CompanyRepositoryPort;

final readonly class ListCompanyHandler
{
    public function __construct(private CompanyRepositoryPort $companies) {}

    /**
     * @return LengthAwarePaginator<int, CompanyData>
     */
    public function handle(int $perPage = 15): LengthAwarePaginator
    {
        return $this->companies->paginate($perPage);
    }
}
