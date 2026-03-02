<?php

declare(strict_types=1);

namespace Modules\Students\Application\Commands\UpdateStudent;

use Illuminate\Support\Facades\Cache;
use Modules\Students\Domain\Exceptions\StudentNotFoundException;
use Modules\Students\Domain\Ports\StudentRepositoryPort;
use Modules\Students\Domain\ValueObjects\StudentId;

final readonly class UpdateStudentHandler
{
    public function __construct(
        private StudentRepositoryPort $repository
    ) {}

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
            notes: $dto->notes,
            active: $dto->active
        );

        $this->repository->save($updatedStudent);

        // Clear cache
        Cache::forget("student_{$command->uuid}");
        try {
            Cache::tags(['students_list'])->flush();
        } catch (\Exception $e) {
            // Tags not supported
        }
    }
}
