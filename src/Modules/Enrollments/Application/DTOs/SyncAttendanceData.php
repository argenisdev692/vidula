<?php

declare(strict_types=1);

namespace Modules\Enrollments\Application\DTOs;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
final class SyncAttendanceData extends Data
{
    /**
     * @param  list<AttendanceMarkData>  $marks
     */
    public function __construct(
        #[DataCollectionOf(AttendanceMarkData::class)]
        public array $marks,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'marks' => ['required', 'array', 'min:1', 'max:2000'],
            'marks.*.enrollment_uuid' => ['required', 'uuid', 'exists:classroom_enrollments,uuid'],
            'marks.*.product_session_uuid' => ['required', 'uuid', 'exists:product_sessions,uuid'],
            'marks.*.attendance_status' => ['required', 'string', 'in:present,absent,late,justified'],
            'marks.*.observation' => ['nullable', 'string', 'max:2000'],
            'marks.*.date' => ['nullable', 'date'],
        ];
    }
}
