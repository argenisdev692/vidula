<?php

declare(strict_types=1);

namespace Modules\Students\Application\Commands\RestoreStudent;

use Illuminate\Support\Facades\Cache;
use Modules\Students\Domain\Ports\StudentRepositoryPort;
use Modules\Students\Domain\ValueObjects\StudentId;
use Shared\Infrastructure\Audit\AuditInterface;

final readonly class RestoreStudentHandler
{
    public function __construct(
        private StudentRepositoryPort $repository,
        private AuditInterface $audit,
    ) {
    }

    public function handle(RestoreStudentCommand $command): void
    {
        $id = new StudentId($command->uuid);
        $student = $this->repository->findById($id);
        $this->repository->restore($id);

        if ($student !== null) {
            $this->audit->log(
                logName: 'students.restored',
                description: "Student restored: {$student->name}",
                properties: [
                    'uuid' => $command->uuid,
                    'name' => $student->name,
                    'email' => $student->email,
                ],
            );
        }

        // Clear cache
        Cache::forget("student_{$command->uuid}");
        try {
            Cache::tags(['students_list'])->flush();
        } catch (\Exception $e) {
            // Tags not supported
        }
    }
}
