<?php

declare(strict_types=1);

namespace Modules\Students\Domain\Ports;

use Modules\Students\Domain\Entities\Student;
use Modules\Students\Domain\ValueObjects\StudentId;

/**
 * StudentRepositoryPort
 */
interface StudentRepositoryPort
{
    public function findById(StudentId $id): ?Student;

    public function findByEmail(string $email): ?Student;

    public function save(Student $student): void;

    public function delete(StudentId $id): void;

    public function restore(StudentId $id): void;

    /**
     * @param array<string, mixed> $filters
     */
    public function findAllPaginated(array $filters = [], int $page = 1, int $perPage = 15): array;
}
