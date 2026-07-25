<?php

declare(strict_types=1);

namespace Modules\VideoExport\Application\DTOs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
final class PresignUploadData extends Data
{
    public function __construct(
        public string $filename,
        public string $contentType,
        public ?int $sizeBytes = null,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'filename' => ['required', 'string', 'max:255'],
            'content_type' => ['required', 'string', 'max:128', 'regex:/^(video\/|application\/pdf|text\/)/'],
            'size_bytes' => ['nullable', 'integer', 'min:1', 'max:'.(int) config('video-export.max_source_bytes', 2147483648)],
        ];
    }
}
