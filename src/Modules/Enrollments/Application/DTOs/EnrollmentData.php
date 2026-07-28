<?php

declare(strict_types=1);

namespace Modules\Enrollments\Application\DTOs;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
final class EnrollmentData extends Data
{
    public function __construct(
        public string $studentUuid,
        public string $classroomUuid,
        public string $enrolledAt,
        public string $enrollmentStatus = 'active',
        public ?float $finalGrade = null,
        public ?string $notes = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'student_uuid' => ['required', 'uuid', 'exists:students,uuid'],
            'classroom_uuid' => ['required', 'uuid', 'exists:classrooms,uuid'],
            'enrolled_at' => ['required', 'date'],
            'enrollment_status' => ['required', 'string', Rule::in(['active', 'suspended', 'completed', 'dropped'])],
            'final_grade' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
