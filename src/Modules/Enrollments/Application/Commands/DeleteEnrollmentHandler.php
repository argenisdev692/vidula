<?php

declare(strict_types=1);

namespace Modules\Enrollments\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Enrollments\Application\Support\EnrollmentCache;
use Modules\Enrollments\Domain\Ports\EnrollmentRepositoryPort;

final readonly class DeleteEnrollmentHandler
{
    public function __construct(private EnrollmentRepositoryPort $enrollments) {}

    public function handle(string $uuid): void
    {
        DB::transaction(fn () => $this->enrollments->softDelete($uuid));

        EnrollmentCache::flush();
    }
}
