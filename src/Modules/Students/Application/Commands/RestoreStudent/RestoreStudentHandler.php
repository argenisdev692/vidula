<?php

declare(strict_types=1);

namespace Modules\Students\Application\Commands\RestoreStudent;

use Illuminate\Support\Facades\Cache;
use Modules\Students\Domain\Ports\StudentRepositoryPort;
use Modules\Students\Domain\ValueObjects\StudentId;

final readonly class RestoreStudentHandler
{
    public function __construct(
        private StudentRepositoryPort $repository
    ) {
    }

    public function handle(RestoreStudentCommand $command): void
    {
        $id = new StudentId($command->id);
        $this->repository->restore($id);

        // Clear cache
        Cache::forget("student_{$command->id}");
        try {
            Cache::tags(['students_list'])->flush();
        } catch (\Exception $e) {
            // Tags not supported
        }
    }
}
