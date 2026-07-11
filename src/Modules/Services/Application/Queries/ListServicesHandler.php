<?php

declare(strict_types=1);

namespace Modules\Services\Application\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Services\Application\DTOs\ServiceFilterData;
use Modules\Services\Domain\Ports\ServiceRepositoryPort;

final readonly class ListServicesHandler
{
    public function __construct(private ServiceRepositoryPort $services) {}

    public function handle(ServiceFilterData $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->services->paginate($filters, $perPage);
    }
}
