<?php

declare(strict_types=1);

namespace Modules\Enrollments\Infrastructure\Persistence\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Modules\Enrollments\Application\DTOs\EnrollmentFilterData;
use Modules\Enrollments\Domain\Ports\EnrollmentRepositoryPort;
use Modules\Enrollments\Infrastructure\Persistence\Eloquent\Models\ClassroomAttendanceEloquentModel;
use Modules\Enrollments\Infrastructure\Persistence\Eloquent\Models\ClassroomEnrollmentEloquentModel;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ClassroomEloquentModel;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductSessionEloquentModel;
use Modules\Students\Infrastructure\Persistence\Eloquent\Models\StudentEloquentModel;
use Shared\Infrastructure\Persistence\Concerns\BulkSoftDeletesByUuid;

final class EloquentEnrollmentRepository implements EnrollmentRepositoryPort
{
    use BulkSoftDeletesByUuid;

    /**
     * @return class-string<ClassroomEnrollmentEloquentModel>
     */
    protected function model(): string
    {
        return ClassroomEnrollmentEloquentModel::class;
    }

    public function paginate(EnrollmentFilterData $filters, int $perPage): LengthAwarePaginator
    {
        return ClassroomEnrollmentEloquentModel::query()
            ->when($filters->status === 'suspended', fn ($q) => $q->onlyTrashed())
            ->applyFilters($filters)
            ->with([
                'student:id,uuid,name,email',
                'classroom:id,uuid,product_id',
                'classroom.product:id,uuid,title,type',
            ])
            ->select([
                'id',
                'uuid',
                'student_id',
                'classroom_id',
                'enrolled_at',
                'enrollment_status',
                'final_grade',
                'notes',
                'created_at',
                'deleted_at',
            ])
            ->orderByDesc('enrolled_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findByUuid(string $uuid): ?ClassroomEnrollmentEloquentModel
    {
        return ClassroomEnrollmentEloquentModel::withTrashed()
            ->with([
                'student:id,uuid,name,email,phone,dni',
                'classroom:id,uuid,product_id,max_students,meet_url',
                'classroom.product:id,uuid,title,type',
                'attendances',
            ])
            ->where('uuid', $uuid)
            ->first();
    }

    public function findClassroomByUuid(string $uuid): ?ClassroomEloquentModel
    {
        return ClassroomEloquentModel::query()
            ->with([
                'product:id,uuid,title,type,currency',
                'product.sessions' => fn ($q) => $q
                    ->orderBy('session_number')
                    ->select([
                        'id',
                        'uuid',
                        'product_id',
                        'session_number',
                        'title',
                        'session_date',
                        'hours',
                    ]),
            ])
            ->where('uuid', $uuid)
            ->first();
    }

    public function findEnrollmentInClassroom(string $enrollmentUuid, int $classroomId): ?ClassroomEnrollmentEloquentModel
    {
        return ClassroomEnrollmentEloquentModel::query()
            ->where('uuid', $enrollmentUuid)
            ->where('classroom_id', $classroomId)
            ->first();
    }

    public function findSessionForProduct(string $sessionUuid, int $productId): ?ProductSessionEloquentModel
    {
        return ProductSessionEloquentModel::query()
            ->where('uuid', $sessionUuid)
            ->where('product_id', $productId)
            ->first();
    }

    public function existsForStudentAndClassroom(int $studentId, int $classroomId, ?string $exceptUuid = null): bool
    {
        return ClassroomEnrollmentEloquentModel::withTrashed()
            ->when($exceptUuid !== null, fn ($q) => $q->where('uuid', '!=', $exceptUuid))
            ->where('student_id', $studentId)
            ->where('classroom_id', $classroomId)
            ->exists();
    }

    public function create(array $attributes): ClassroomEnrollmentEloquentModel
    {
        return ClassroomEnrollmentEloquentModel::query()->create($attributes);
    }

    public function update(ClassroomEnrollmentEloquentModel $enrollment, array $attributes): ClassroomEnrollmentEloquentModel
    {
        $enrollment->update($attributes);

        return $enrollment->refresh();
    }

    public function softDelete(string $uuid): bool
    {
        return (bool) ClassroomEnrollmentEloquentModel::query()->where('uuid', $uuid)->delete();
    }

    public function restore(string $uuid): bool
    {
        return (bool) ClassroomEnrollmentEloquentModel::onlyTrashed()->where('uuid', $uuid)->restore();
    }

    public function upsertAttendanceMarks(array $marks): int
    {
        $count = 0;

        foreach ($marks as $mark) {
            $existing = ClassroomAttendanceEloquentModel::withTrashed()
                ->where('enrollment_id', $mark['enrollment_id'])
                ->where('product_session_id', $mark['product_session_id'])
                ->first();

            if ($existing !== null) {
                if ($existing->trashed()) {
                    $existing->restore();
                }
                $existing->update([
                    'date' => $mark['date'],
                    'attendance_status' => $mark['attendance_status'],
                    'observation' => $mark['observation'],
                ]);
                $count++;

                continue;
            }

            ClassroomAttendanceEloquentModel::query()->create([
                'uuid' => (string) Str::uuid7(),
                'enrollment_id' => $mark['enrollment_id'],
                'product_session_id' => $mark['product_session_id'],
                'date' => $mark['date'],
                'attendance_status' => $mark['attendance_status'],
                'observation' => $mark['observation'],
            ]);
            $count++;
        }

        return $count;
    }

    public function buildAttendanceSheet(ClassroomEloquentModel $classroom): array
    {
        $product = $classroom->product;
        $sessions = $product?->sessions ?? collect();

        $enrollments = ClassroomEnrollmentEloquentModel::query()
            ->where('classroom_id', $classroom->id)
            ->with(['student:id,uuid,name,email'])
            ->orderBy('enrolled_at')
            ->get(['id', 'uuid', 'student_id', 'enrolled_at', 'enrollment_status']);

        $marks = ClassroomAttendanceEloquentModel::query()
            ->whereIn('enrollment_id', $enrollments->pluck('id'))
            ->with(['session:id,uuid'])
            ->get(['id', 'uuid', 'enrollment_id', 'product_session_id', 'date', 'attendance_status', 'observation']);

        return [
            'classroom' => $classroom,
            'sessions' => $sessions->map(static fn ($session): array => [
                'uuid' => $session->uuid,
                'session_number' => $session->session_number,
                'title' => $session->title,
                'session_date' => $session->session_date?->toDateString(),
                'hours' => $session->hours,
            ])->values()->all(),
            'enrollments' => $enrollments->map(static fn (ClassroomEnrollmentEloquentModel $row): array => [
                'uuid' => $row->uuid,
                'enrolled_at' => $row->enrolled_at?->toDateString(),
                'enrollment_status' => $row->enrollment_status instanceof \BackedEnum
                    ? $row->enrollment_status->value
                    : (string) $row->enrollment_status,
                'student' => [
                    'uuid' => $row->student?->uuid,
                    'name' => $row->student?->name,
                    'email' => $row->student?->email,
                ],
            ])->values()->all(),
            'marks' => $marks->map(static fn (ClassroomAttendanceEloquentModel $mark): array => [
                'uuid' => $mark->uuid,
                'enrollment_uuid' => $enrollments->firstWhere('id', $mark->enrollment_id)?->uuid,
                'product_session_uuid' => $mark->session?->uuid,
                'date' => $mark->date?->toDateString(),
                'attendance_status' => $mark->attendance_status instanceof \BackedEnum
                    ? $mark->attendance_status->value
                    : (string) $mark->attendance_status,
                'observation' => $mark->observation,
            ])->values()->all(),
        ];
    }

    public function listActiveStudentsForForm(int $limit = 300): array
    {
        return StudentEloquentModel::query()
            ->where('active', true)
            ->orderBy('name')
            ->select(['uuid', 'name', 'email'])
            ->limit($limit)
            ->get()
            ->map(static fn (StudentEloquentModel $student): array => [
                'uuid' => $student->uuid,
                'name' => $student->name,
                'email' => $student->email,
            ])
            ->values()
            ->all();
    }

    public function listClassroomsForForm(int $limit = 200): array
    {
        return ClassroomEloquentModel::query()
            ->with('product:id,uuid,title,type')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(static fn (ClassroomEloquentModel $classroom): array => [
                'uuid' => $classroom->uuid,
                'title' => $classroom->product?->title ?? 'Classroom',
                'product_type' => $classroom->product?->type instanceof \BackedEnum
                    ? $classroom->product->type->value
                    : (string) ($classroom->product?->type ?? 'classroom'),
            ])
            ->values()
            ->all();
    }

    public function findStudentIdByUuid(string $uuid): ?int
    {
        $id = StudentEloquentModel::query()->where('uuid', $uuid)->value('id');

        return $id !== null ? (int) $id : null;
    }

    public function findClassroomIdByUuid(string $uuid): ?int
    {
        $id = ClassroomEloquentModel::query()->where('uuid', $uuid)->value('id');

        return $id !== null ? (int) $id : null;
    }
}
