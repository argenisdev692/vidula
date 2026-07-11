<?php

declare(strict_types=1);

namespace Modules\Portfolio\Application\Queries;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Portfolio\Domain\Ports\PortfolioRepositoryPort;
use Modules\Portfolio\Infrastructure\Persistence\Eloquent\Models\PortfolioEloquentModel;

final readonly class GetPortfolioHandler
{
    public function __construct(private PortfolioRepositoryPort $portfolios) {}

    public function handle(string $uuid): PortfolioEloquentModel
    {
        return $this->portfolios->findByUuid($uuid)
            ?? throw (new ModelNotFoundException)->setModel(PortfolioEloquentModel::class, [$uuid]);
    }
}
