<?php

declare(strict_types=1);

namespace Modules\Enrollments\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Enrollments\Application\Support\EnrollmentCache;
use Modules\Enrollments\Domain\Ports\EnrollmentRepositoryPort;
use Shared\Application\DTOs\BulkUuidsData;

final readonly class BulkRestoreEnrollmentsHandler
{
    public function __construct(private EnrollmentRepositoryPort $enrollments) {}

    public function handle(BulkUuidsData $data): int
    {
        $count = DB::transaction(fn () => $this->enrollments->bulkRestoreByUuid($data->uuids));

        EnrollmentCache::flush();

        return $count;
    }
}
