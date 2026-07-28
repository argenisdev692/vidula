<?php

declare(strict_types=1);

namespace Modules\Enrollments\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Enrollments\Application\Support\EnrollmentCache;
use Modules\Enrollments\Domain\Ports\EnrollmentRepositoryPort;

final readonly class RestoreEnrollmentHandler
{
    public function __construct(private EnrollmentRepositoryPort $enrollments) {}

    public function handle(string $uuid): void
    {
        DB::transaction(fn () => $this->enrollments->restore($uuid));

        EnrollmentCache::flush();
    }
}
