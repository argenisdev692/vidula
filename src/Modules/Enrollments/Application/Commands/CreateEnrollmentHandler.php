<?php

declare(strict_types=1);

namespace Modules\Enrollments\Application\Commands;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Enrollments\Application\DTOs\EnrollmentData;
use Modules\Enrollments\Application\Support\EnrollmentCache;
use Modules\Enrollments\Domain\Ports\EnrollmentRepositoryPort;
use Modules\Enrollments\Infrastructure\Persistence\Eloquent\Models\ClassroomEnrollmentEloquentModel;

final readonly class CreateEnrollmentHandler
{
    public function __construct(private EnrollmentRepositoryPort $enrollments) {}

    #[\NoDiscard]
    public function handle(EnrollmentData $data): ClassroomEnrollmentEloquentModel
    {
        $studentId = $this->enrollments->findStudentIdByUuid($data->studentUuid)
            ?? throw ValidationException::withMessages([
                'student_uuid' => [__('The selected student is invalid.')],
            ]);

        $classroomId = $this->enrollments->findClassroomIdByUuid($data->classroomUuid)
            ?? throw ValidationException::withMessages([
                'classroom_uuid' => [__('The selected classroom is invalid.')],
            ]);

        if ($this->enrollments->existsForStudentAndClassroom($studentId, $classroomId)) {
            throw ValidationException::withMessages([
                'student_uuid' => [__('This student is already enrolled in that classroom.')],
            ]);
        }

        $enrollment = DB::transaction(fn () => $this->enrollments->create([
            'student_id' => $studentId,
            'classroom_id' => $classroomId,
            'enrolled_at' => $data->enrolledAt,
            'enrollment_status' => $data->enrollmentStatus,
            'final_grade' => $data->finalGrade,
            'notes' => $data->notes,
        ]));

        EnrollmentCache::flush();

        return $enrollment;
    }
}
