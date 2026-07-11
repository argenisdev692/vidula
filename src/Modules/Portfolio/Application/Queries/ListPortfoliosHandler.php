<?php

declare(strict_types=1);

namespace Modules\Portfolio\Application\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Portfolio\Application\DTOs\PortfolioFilterData;
use Modules\Portfolio\Domain\Ports\PortfolioRepositoryPort;

final readonly class ListPortfoliosHandler
{
    public function __construct(private PortfolioRepositoryPort $portfolios) {}

    public function handle(PortfolioFilterData $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->portfolios->paginate($filters, $perPage);
    }
}
