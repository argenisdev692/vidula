<?php

declare(strict_types=1);

namespace Modules\Enrollments\Application\DTOs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
final class AttendanceMarkData extends Data
{
    public function __construct(
        public string $enrollmentUuid,
        public string $productSessionUuid,
        public string $attendanceStatus,
        public ?string $observation = null,
        public ?string $date = null,
    ) {}
}
