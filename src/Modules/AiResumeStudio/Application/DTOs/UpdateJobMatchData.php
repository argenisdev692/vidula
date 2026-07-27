<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Application\DTOs;

use Illuminate\Validation\Rule;
use Modules\AiResumeStudio\Domain\Enums\ApplicationStatus;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
final class UpdateJobMatchData extends Data
{
    public function __construct(
        public string $applicationStatus,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'application_status' => ['required', 'string', Rule::in(ApplicationStatus::values())],
        ];
    }
}
