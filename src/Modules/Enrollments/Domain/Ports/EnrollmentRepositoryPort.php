<?php

declare(strict_types=1);

namespace Modules\Enrollments\Domain\Ports;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Enrollments\Application\DTOs\EnrollmentFilterData;
use Modules\Enrollments\Infrastructure\Persistence\Eloquent\Models\ClassroomEnrollmentEloquentModel;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ClassroomEloquentModel;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductSessionEloquentModel;

/**
 * Persistence port for classroom enrollments + attendance marks.
 *
 * Eloquent return types are intentional while Domain Entity / Mapper remain
 * optional (Eloquent model is 1:1 with the aggregate).
 */
interface EnrollmentRepositoryPort
{
    /** @return LengthAwarePaginator<int, ClassroomEnrollmentEloquentModel> */
    public function paginate(EnrollmentFilterData $filters, int $perPage): LengthAwarePaginator;

    public function findByUuid(string $uuid): ?ClassroomEnrollmentEloquentModel;

    public function findClassroomByUuid(string $uuid): ?ClassroomEloquentModel;

    public function findEnrollmentInClassroom(string $enrollmentUuid, int $classroomId): ?ClassroomEnrollmentEloquentModel;

    public function findSessionForProduct(string $sessionUuid, int $productId): ?ProductSessionEloquentModel;

    public function existsForStudentAndClassroom(int $studentId, int $classroomId, ?string $exceptUuid = null): bool;

    /** @param  array<string, mixed>  $attributes */
    public function create(array $attributes): ClassroomEnrollmentEloquentModel;

    /** @param  array<string, mixed>  $attributes */
    public function update(ClassroomEnrollmentEloquentModel $enrollment, array $attributes): ClassroomEnrollmentEloquentModel;

    public function softDelete(string $uuid): bool;

    public function restore(string $uuid): bool;

    /** @param  list<string>  $uuids */
    public function bulkSoftDeleteByUuid(array $uuids): int;

    /** @param  list<string>  $uuids */
    public function bulkRestoreByUuid(array $uuids): int;

    /**
     * @param  list<array{enrollment_id: int, product_session_id: int, date: string, attendance_status: string, observation: ?string}>  $marks
     */
    public function upsertAttendanceMarks(array $marks): int;

    /**
     * @return array{
     *     classroom: ClassroomEloquentModel,
     *     sessions: list<array<string, mixed>>,
     *     enrollments: list<array<string, mixed>>,
     *     marks: list<array<string, mixed>>
     * }
     */
    public function buildAttendanceSheet(ClassroomEloquentModel $classroom): array;

    /**
     * @return list<array{uuid: string, name: string, email: string}>
     */
    public function listActiveStudentsForForm(int $limit = 300): array;

    /**
     * @return list<array{uuid: string, title: string, product_type: string}>
     */
    public function listClassroomsForForm(int $limit = 200): array;

    public function findStudentIdByUuid(string $uuid): ?int;

    public function findClassroomIdByUuid(string $uuid): ?int;
}
