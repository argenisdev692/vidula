<?php

declare(strict_types=1);

namespace Modules\Students\Application\Commands\CreateStudent;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\Students\Domain\Entities\Student;
use Modules\Students\Domain\Ports\StudentRepositoryPort;
use Modules\Students\Domain\ValueObjects\StudentId;

/**
 * CreateStudentHandler
 */
final readonly class CreateStudentHandler
{
    public function __construct(
        private StudentRepositoryPort $repository
    ) {
    }

    public function handle(CreateStudentCommand $command): void
    {
        $dto = $command->dto;
        $uuid = Str::uuid()->toString();

        $student = Student::create(
            id: new StudentId($uuid),
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

        $this->repository->save($student);

        // Clear cache
        try {
            Cache::tags(['students_list'])->flush();
        } catch (\Exception $e) {
            // Tags not supported, cache will expire naturally
        }
    }
}
