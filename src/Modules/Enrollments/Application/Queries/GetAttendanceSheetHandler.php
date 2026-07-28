<?php

declare(strict_types=1);

namespace Modules\Enrollments\Application\Queries;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Enrollments\Domain\Ports\EnrollmentRepositoryPort;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ClassroomEloquentModel;

final readonly class GetAttendanceSheetHandler
{
    public function __construct(private EnrollmentRepositoryPort $enrollments) {}

    /**
     * @return array{
     *     classroom: ClassroomEloquentModel,
     *     sessions: list<array<string, mixed>>,
     *     enrollments: list<array<string, mixed>>,
     *     marks: list<array<string, mixed>>
     * }
     */
    public function handle(string $classroomUuid): array
    {
        $classroom = $this->enrollments->findClassroomByUuid($classroomUuid)
            ?? throw (new ModelNotFoundException)->setModel(ClassroomEloquentModel::class, [$classroomUuid]);

        return $this->enrollments->buildAttendanceSheet($classroom);
    }
}
