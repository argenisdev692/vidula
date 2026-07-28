<?php

declare(strict_types=1);

namespace Modules\Products\Application\DTOs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Thin 1:1 detail carried inside {@see ProductData} when the product type is
 * `classroom`. Nested rules live on the parent DTO (`classroom.*`).
 */
#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
final class ClassroomDetailData extends Data
{
    public function __construct(
        public ?int $maxStudents = null,
        public ?string $meetUrl = null,
        public ?string $objectives = null,
        public ?string $requirements = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'max_students' => $this->maxStudents,
            'meet_url' => $this->meetUrl,
            'objectives' => $this->objectives,
            'requirements' => $this->requirements,
        ];
    }
}
