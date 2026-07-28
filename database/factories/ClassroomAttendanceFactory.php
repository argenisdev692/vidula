<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Enrollments\Domain\Enums\AttendanceStatus;
use Modules\Enrollments\Infrastructure\Persistence\Eloquent\Models\ClassroomAttendanceEloquentModel;
use Modules\Enrollments\Infrastructure\Persistence\Eloquent\Models\ClassroomEnrollmentEloquentModel;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductSessionEloquentModel;

/**
 * @extends Factory<ClassroomAttendanceEloquentModel>
 */
final class ClassroomAttendanceFactory extends Factory
{
    /**
     * @var class-string<ClassroomAttendanceEloquentModel>
     */
    protected $model = ClassroomAttendanceEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid7(),
            'enrollment_id' => ClassroomEnrollmentEloquentModel::factory(),
            'product_session_id' => ProductSessionEloquentModel::factory(),
            'product_session_topic_id' => null,
            'date' => now()->toDateString(),
            'attendance_status' => AttendanceStatus::Present,
            'observation' => null,
        ];
    }

    public function absent(): static
    {
        return $this->state(fn (): array => [
            'attendance_status' => AttendanceStatus::Absent,
        ]);
    }

    public function late(): static
    {
        return $this->state(fn (): array => [
            'attendance_status' => AttendanceStatus::Late,
        ]);
    }
}
