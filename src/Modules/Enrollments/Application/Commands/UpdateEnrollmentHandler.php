<?php

declare(strict_types=1);

namespace Modules\Enrollments\Application\Commands;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Enrollments\Application\DTOs\EnrollmentData;
use Modules\Enrollments\Application\Support\EnrollmentCache;
use Modules\Enrollments\Domain\Ports\EnrollmentRepositoryPort;
use Modules\Enrollments\Infrastructure\Persistence\Eloquent\Models\ClassroomEnrollmentEloquentModel;

final readonly class UpdateEnrollmentHandler
{
    public function __construct(private EnrollmentRepositoryPort $enrollments) {}

    #[\NoDiscard]
    public function handle(ClassroomEnrollmentEloquentModel $enrollment, EnrollmentData $data): ClassroomEnrollmentEloquentModel
    {
        $studentId = $this->enrollments->findStudentIdByUuid($data->studentUuid)
            ?? throw ValidationException::withMessages([
                'student_uuid' => [__('The selected student is invalid.')],
            ]);

        $classroomId = $this->enrollments->findClassroomIdByUuid($data->classroomUuid)
            ?? throw ValidationException::withMessages([
                'classroom_uuid' => [__('The selected classroom is invalid.')],
            ]);

        if ($this->enrollments->existsForStudentAndClassroom($studentId, $classroomId, $enrollment->uuid)) {
            throw ValidationException::withMessages([
                'student_uuid' => [__('This student is already enrolled in that classroom.')],
            ]);
        }

        $updated = DB::transaction(fn () => $this->enrollments->update($enrollment, [
            'student_id' => $studentId,
            'classroom_id' => $classroomId,
            'enrolled_at' => $data->enrolledAt,
            'enrollment_status' => $data->enrollmentStatus,
            'final_grade' => $data->finalGrade,
            'notes' => $data->notes,
        ]));

        EnrollmentCache::flush();

        return $updated;
    }
}
