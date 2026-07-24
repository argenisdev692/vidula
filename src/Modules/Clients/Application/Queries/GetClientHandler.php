<?php

declare(strict_types=1);

namespace Modules\Clients\Application\Queries;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Clients\Domain\Ports\ClientRepositoryPort;
use Modules\Clients\Infrastructure\Persistence\Eloquent\Models\ClientEloquentModel;

final readonly class GetClientHandler
{
    public function __construct(private ClientRepositoryPort $clients) {}

    public function handle(string $uuid): ClientEloquentModel
    {
        return $this->clients->findByUuid($uuid)
            ?? throw (new ModelNotFoundException)->setModel(ClientEloquentModel::class, [$uuid]);
    }
}
