<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Enrollments\Domain\Enums\EnrollmentStatus;
use Modules\Enrollments\Infrastructure\Persistence\Eloquent\Models\ClassroomEnrollmentEloquentModel;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ClassroomEloquentModel;
use Modules\Students\Infrastructure\Persistence\Eloquent\Models\StudentEloquentModel;

/**
 * @extends Factory<ClassroomEnrollmentEloquentModel>
 */
final class ClassroomEnrollmentFactory extends Factory
{
    /**
     * @var class-string<ClassroomEnrollmentEloquentModel>
     */
    protected $model = ClassroomEnrollmentEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid7(),
            'student_id' => StudentEloquentModel::factory(),
            'classroom_id' => ClassroomEloquentModel::factory(),
            'enrolled_at' => now()->toDateString(),
            'enrollment_status' => EnrollmentStatus::Active,
            'final_grade' => null,
            'notes' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'enrollment_status' => EnrollmentStatus::Completed,
        ]);
    }

    public function dropped(): static
    {
        return $this->state(fn (): array => [
            'enrollment_status' => EnrollmentStatus::Dropped,
        ]);
    }
}
