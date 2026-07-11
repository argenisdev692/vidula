<?php

declare(strict_types=1);

namespace Modules\Services\Application\Queries;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Services\Domain\Ports\ServiceRepositoryPort;
use Modules\Services\Infrastructure\Persistence\Eloquent\Models\ServiceEloquentModel;

final readonly class GetServiceHandler
{
    public function __construct(private ServiceRepositoryPort $services) {}

    public function handle(string $uuid): ServiceEloquentModel
    {
        return $this->services->findByUuid($uuid)
            ?? throw (new ModelNotFoundException)->setModel(ServiceEloquentModel::class, [$uuid]);
    }
}
