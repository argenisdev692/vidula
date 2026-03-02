<?php
declare(strict_types=1);

namespace Modules\Students\Infrastructure\Persistence\Mappers;

use Modules\Students\Domain\Entities\Student;
use Modules\Students\Domain\ValueObjects\StudentId;
use Modules\Students\Infrastructure\Persistence\Eloquent\Models\StudentEloquentModel;

final class StudentMapper
{
    #[\NoDiscard]
    public static function toDomain(StudentEloquentModel $model): Student
    {
        return $model
            |> (fn($m) => [
                'id' => new StudentId($m->uuid),
                'name' => $m->name,
                'email' => $m->email,
                'phone' => $m->phone,
                'dni' => $m->dni,
                'birthDate' => $m->birth_date,
                'address' => $m->address,
                'avatar' => $m->avatar,
                'notes' => $m->notes,
                'active' => (bool) $m->active,
                'createdAt' => $m->created_at?->toIso8601String(),
                'updatedAt' => $m->updated_at?->toIso8601String(),
                'deletedAt' => $m->deleted_at?->toIso8601String()
            ])
            |> (fn($data) => new Student(...$data));
    }
}
