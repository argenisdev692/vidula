<?php

declare(strict_types=1);

namespace Modules\Enrollments\Application\Queries;

use Modules\Enrollments\Domain\Ports\EnrollmentRepositoryPort;

final readonly class GetEnrollmentFormOptionsHandler
{
    public function __construct(private EnrollmentRepositoryPort $enrollments) {}

    /**
     * @return array{
     *     students: list<array{uuid: string, name: string, email: string}>,
     *     classrooms: list<array{uuid: string, title: string, product_type: string}>
     * }
     */
    public function handle(): array
    {
        return [
            'students' => $this->enrollments->listActiveStudentsForForm(),
            'classrooms' => $this->enrollments->listClassroomsForForm(),
        ];
    }
}
