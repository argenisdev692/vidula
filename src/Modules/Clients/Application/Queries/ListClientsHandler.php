<?php

declare(strict_types=1);

namespace Modules\Clients\Application\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Clients\Application\DTOs\ClientFilterData;
use Modules\Clients\Domain\Ports\ClientRepositoryPort;

final readonly class ListClientsHandler
{
    public function __construct(private ClientRepositoryPort $clients) {}

    public function handle(ClientFilterData $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->clients->paginate($filters, $perPage);
    }
}
