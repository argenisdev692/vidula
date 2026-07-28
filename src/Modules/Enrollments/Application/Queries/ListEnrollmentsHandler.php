<?php

declare(strict_types=1);

namespace Modules\Enrollments\Application\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Enrollments\Application\DTOs\EnrollmentFilterData;
use Modules\Enrollments\Application\Support\EnrollmentCache;
use Modules\Enrollments\Domain\Ports\EnrollmentRepositoryPort;

final readonly class ListEnrollmentsHandler
{
    public function __construct(private EnrollmentRepositoryPort $enrollments) {}

    public function handle(EnrollmentFilterData $filters, int $perPage): LengthAwarePaginator
    {
        $fingerprint = hash('xxh128', json_encode([
            $filters->search,
            $filters->status,
            $filters->dateFrom,
            $filters->dateTo,
            $filters->enrollmentStatus,
            $filters->classroomUuid,
            $filters->studentUuid,
            $perPage,
        ], JSON_THROW_ON_ERROR));

        return EnrollmentCache::rememberList(
            $fingerprint,
            fn (): LengthAwarePaginator => $this->enrollments->paginate($filters, $perPage),
        );
    }
}
