<?php

declare(strict_types=1);

namespace Modules\Students\Application\Commands\DeleteStudent;

use Illuminate\Support\Facades\Cache;
use Modules\Students\Domain\Exceptions\StudentNotFoundException;
use Modules\Students\Domain\Ports\StudentRepositoryPort;
use Modules\Students\Domain\ValueObjects\StudentId;
use Shared\Infrastructure\Audit\AuditInterface;

final readonly class DeleteStudentHandler
{
    public function __construct(
        private StudentRepositoryPort $repository,
        private AuditInterface $audit,
    ) {}

    public function handle(DeleteStudentCommand $command): void
    {
        $id = new StudentId($command->uuid);
        $student = $this->repository->findById($id);

        if (null === $student) {
            throw StudentNotFoundException::forId($command->uuid);
        }

        $this->repository->delete($id);

        $this->audit->log(
            logName: 'students.deleted',
            description: "Student deleted: {$student->name}",
            properties: [
                'uuid' => $command->uuid,
                'name' => $student->name,
                'email' => $student->email,
            ],
        );

        // Clear caches
        Cache::forget("student_{$command->uuid}");
        try {
            Cache::tags(['students_list'])->flush();
        } catch (\Exception $e) {
            // Tags not supported
        }
    }
}
