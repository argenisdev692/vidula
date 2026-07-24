<?php

declare(strict_types=1);

namespace Modules\Clients\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Clients\Domain\Ports\ClientRepositoryPort;
use Shared\Application\DTOs\BulkUuidsData;

final readonly class BulkRestoreClientsHandler
{
    public function __construct(private ClientRepositoryPort $clients) {}

    public function handle(BulkUuidsData $data): int
    {
        return DB::transaction(fn () => $this->clients->bulkRestoreByUuid($data->uuids));
    }
}
