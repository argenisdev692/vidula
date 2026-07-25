<?php

declare(strict_types=1);

namespace Modules\Cvs\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Cvs\Domain\Ports\CvRepositoryPort;
use Shared\Application\DTOs\BulkUuidsData;

final readonly class BulkDeleteCvsHandler
{
    public function __construct(private CvRepositoryPort $cvs) {}

    public function handle(BulkUuidsData $data): int
    {
        return DB::transaction(fn (): int => $this->cvs->bulkSoftDeleteByUuid($data->uuids));
    }
}
