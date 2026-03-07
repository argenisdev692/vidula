<?php

declare(strict_types=1);

namespace Modules\Students\Application\Commands\UpdateStudent;

use Illuminate\Support\Facades\Cache;
use Modules\Students\Domain\Exceptions\StudentNotFoundException;
use Modules\Students\Domain\Ports\StudentRepositoryPort;
use Modules\Students\Domain\ValueObjects\StudentId;
use Shared\Infrastructure\Audit\AuditInterface;

final readonly class UpdateStudentHandler
{
    public function __construct(
        private StudentRepositoryPort $repository,
        private AuditInterface $audit,
    ) {
    }

    public function handle(UpdateStudentCommand $command): void
    {
        $student = $this->repository->findById(new StudentId($command->uuid));

        if (null === $student) {
            throw StudentNotFoundException::forId($command->uuid);
        }

        $dto = $command->dto;

        $updatedStudent = $student->update(
            name: $dto->name,
            email: $dto->email,
            phone: $dto->phone,
            dni: $dto->dni,
            birthDate: $dto->birthDate,
            address: $dto->address,
            avatar: $dto->avatar,
            notes: $dto->notes,
            status: $dto->status,
            active: $dto->active
        );

        $this->repository->save($updatedStudent);

        $this->audit->log(
            logName: 'students.updated',
            description: "Student updated: {$updatedStudent->name}",
            properties: [
                'uuid' => $command->uuid,
                'name' => $updatedStudent->name,
                'email' => $updatedStudent->email,
                'status' => $updatedStudent->status,
                'active' => $updatedStudent->active,
            ],
        );

        // Clear cache
        Cache::forget("student_{$command->uuid}");
        try {
            Cache::tags(['students_list'])->flush();
        } catch (\Exception $e) {
            // Tags not supported
        }
    }
}
