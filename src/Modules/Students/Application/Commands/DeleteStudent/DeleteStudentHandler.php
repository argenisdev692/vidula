<?php

declare(strict_types=1);

namespace Modules\Students\Application\Commands\DeleteStudent;

use Illuminate\Support\Facades\Cache;
use Modules\Students\Domain\Exceptions\StudentNotFoundException;
use Modules\Students\Domain\Ports\StudentRepositoryPort;
use Modules\Students\Domain\ValueObjects\StudentId;

final readonly class DeleteStudentHandler
{
    public function __construct(
        private StudentRepositoryPort $repository
    ) {}

    public function handle(DeleteStudentCommand $command): void
    {
        $id = new StudentId($command->id);
        $student = $this->repository->findById($id);

        if (null === $student) {
            throw StudentNotFoundException::forId($command->id);
        }

        $this->repository->delete($id);

        // Clear caches
        Cache::forget("student_{$command->id}");
        try {
            Cache::tags(['students_list'])->flush();
        } catch (\Exception $e) {
            // Tags not supported
        }
    }
}
