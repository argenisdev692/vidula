<?php

declare(strict_types=1);

namespace Modules\Enrollments\Application\Commands;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Enrollments\Application\DTOs\AttendanceMarkData;
use Modules\Enrollments\Application\DTOs\SyncAttendanceData;
use Modules\Enrollments\Application\Support\EnrollmentCache;
use Modules\Enrollments\Domain\Ports\EnrollmentRepositoryPort;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ClassroomEloquentModel;

final readonly class SyncAttendanceHandler
{
    public function __construct(private EnrollmentRepositoryPort $enrollments) {}

    public function handle(string $classroomUuid, SyncAttendanceData $data): int
    {
        $classroom = $this->enrollments->findClassroomByUuid($classroomUuid)
            ?? throw (new ModelNotFoundException)->setModel(ClassroomEloquentModel::class, [$classroomUuid]);

        $productId = (int) $classroom->product_id;
        $payload = [];

        foreach ($data->marks as $mark) {
            if (! $mark instanceof AttendanceMarkData) {
                $mark = AttendanceMarkData::from($mark);
            }

            $enrollment = $this->enrollments->findEnrollmentInClassroom($mark->enrollmentUuid, (int) $classroom->id);

            if ($enrollment === null) {
                throw ValidationException::withMessages([
                    'marks' => [__('One or more enrollments do not belong to this classroom.')],
                ]);
            }

            $session = $this->enrollments->findSessionForProduct($mark->productSessionUuid, $productId);

            if ($session === null) {
                throw ValidationException::withMessages([
                    'marks' => [__('One or more sessions do not belong to this classroom product.')],
                ]);
            }

            $date = $mark->date
                ?? $session->session_date?->toDateString()
                ?? now()->toDateString();

            $payload[] = [
                'enrollment_id' => $enrollment->id,
                'product_session_id' => $session->id,
                'date' => $date,
                'attendance_status' => $mark->attendanceStatus,
                'observation' => $mark->observation,
            ];
        }

        $count = DB::transaction(fn () => $this->enrollments->upsertAttendanceMarks($payload));

        EnrollmentCache::flush();

        return $count;
    }
}
