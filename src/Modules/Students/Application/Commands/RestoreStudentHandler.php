<?php

declare(strict_types=1);

namespace Modules\Students\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Students\Domain\Ports\StudentRepositoryPort;

final readonly class RestoreStudentHandler
{
    public function __construct(private StudentRepositoryPort $students) {}

    public function handle(string $uuid): bool
    {
        return DB::transaction(fn () => $this->students->restore($uuid));
    }
}
