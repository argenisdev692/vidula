<?php

declare(strict_types=1);

namespace Modules\Students\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Students\Domain\Ports\StudentRepositoryPort;
use Shared\Application\DTOs\BulkUuidsData;

final readonly class BulkDeleteStudentsHandler
{
    public function __construct(private StudentRepositoryPort $students) {}

    public function handle(BulkUuidsData $data): int
    {
        return DB::transaction(fn () => $this->students->bulkSoftDeleteByUuid($data->uuids));
    }
}
