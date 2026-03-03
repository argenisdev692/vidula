<?php

declare(strict_types=1);

namespace Modules\Students\Infrastructure\Persistence\Repositories;

use Modules\Students\Domain\Entities\Student;
use Modules\Students\Domain\Ports\StudentRepositoryPort;
use Modules\Students\Domain\ValueObjects\StudentId;
use Modules\Students\Infrastructure\Persistence\Eloquent\Models\StudentEloquentModel;
use Modules\Students\Infrastructure\Persistence\Mappers\StudentMapper;

/**
 * EloquentStudentRepository
 */
final class EloquentStudentRepository implements StudentRepositoryPort
{
    public function findById(StudentId $id): ?Student
    {
        $model = StudentEloquentModel::withTrashed()
            ->where('uuid', $id->value)
            ->first();

        return $model ? StudentMapper::toDomain($model) : null;
    }

    public function findByEmail(string $email): ?Student
    {
        $model = StudentEloquentModel::withTrashed()
            ->where('email', $email)
            ->first();

        return $model ? StudentMapper::toDomain($model) : null;
    }

    public function save(Student $student): void
    {
        $model = StudentEloquentModel::withTrashed()
            ->where('uuid', $student->id->value)
            ->first() ?? new StudentEloquentModel();

        $model->fill([
            'uuid' => $student->id->value,
            'name' => $student->name,
            'email' => $student->email,
            'phone' => $student->phone,
            'dni' => $student->dni,
            'birth_date' => $student->birthDate,
            'address' => $student->address,
            'avatar' => $student->avatar,
            'notes' => $student->notes,
            'status' => $student->status,
            'active' => $student->active,
            'deleted_at' => $student->deletedAt,
        ]);

        $model->save();
    }

    public function delete(StudentId $id): void
    {
        StudentEloquentModel::query()->where('uuid', $id->value)->delete();
    }

    public function restore(StudentId $id): void
    {
        StudentEloquentModel::query()->withTrashed()->where('uuid', $id->value)->restore();
    }

    public function findAllPaginated(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $query = StudentEloquentModel::query()
            ->withTrashed()
            ->when($filters['email'] ?? null, fn($q, $email) => $q->where('email', $email))
            ->when($filters['status'] ?? null, fn($q, $status) => $q->where('status', $status))
            ->when(
                $filters['search'] ?? null,
                fn($q, $search) =>
                $q->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('dni', 'like', "%{$search}%");
                })
            )
            ->inDateRange($filters['dateFrom'] ?? null, $filters['dateTo'] ?? null)
            ->orderBy($filters['sortBy'] ?? 'created_at', $filters['sortDir'] ?? 'desc');

        $paginator = $query->paginate(perPage: $perPage, page: $page);

        return [
            'data' => array_map(
                fn(StudentEloquentModel $model) => StudentMapper::toDomain($model),
                $paginator->items()
            ),
            'total' => $paginator->total(),
            'perPage' => $paginator->perPage(),
            'currentPage' => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
        ];
    }
}
