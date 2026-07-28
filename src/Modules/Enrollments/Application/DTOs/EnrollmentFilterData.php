<?php

declare(strict_types=1);

namespace Modules\Enrollments\Application\DTOs;

use Shared\Application\DTOs\SoftDeleteFilterData;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * List/export filter. Soft-delete `status` is active|suspended; optional
 * domain filters narrow by enrollment_status, classroom, or student.
 */
#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
final class EnrollmentFilterData extends SoftDeleteFilterData
{
    public function __construct(
        ?string $search = null,
        ?string $status = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        public ?string $enrollmentStatus = null,
        public ?string $classroomUuid = null,
        public ?string $studentUuid = null,
    ) {
        parent::__construct($search, $status, $dateFrom, $dateTo);
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            ...self::baseRules(),
            'status' => ['nullable', 'string', 'in:active,suspended'],
            'enrollment_status' => ['nullable', 'string', 'in:active,suspended,completed,dropped'],
            'classroom_uuid' => ['nullable', 'uuid', 'exists:classrooms,uuid'],
            'student_uuid' => ['nullable', 'uuid', 'exists:students,uuid'],
        ];
    }
}
