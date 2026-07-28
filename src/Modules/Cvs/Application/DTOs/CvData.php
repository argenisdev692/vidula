<?php

declare(strict_types=1);

namespace Modules\Cvs\Application\DTOs;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Modules\Cvs\Domain\Enums\CvNiche;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Fused create/update DTO for a CV upload. File is required on create
 * (enforced in CreateCvHandler); optional on update to replace the stored file.
 */
#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
final class CvData extends Data
{
    public function __construct(
        public string $title,
        public string $niche = 'fullstack',
        public bool $isPrimary = false,
        public ?UploadedFile $file = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'niche' => ['required', 'string', Rule::in(CvNiche::values())],
            'is_primary' => ['boolean'],
            'file' => [
                'nullable',
                'file',
                'max:5120', // 5 MB
                'extensions:pdf,md,markdown',
                'mimetypes:text/markdown,text/plain,text/x-markdown,application/pdf',
            ],
        ];
    }
}
