<?php

declare(strict_types=1);

namespace Modules\Products\Application\DTOs;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Replacement upload for an existing material (spec US-4).
 *
 * The allowlist is deliberately narrow — Markdown and PDF only. `extensions`
 * checks the real filename suffix and `mimetypes` the sniffed content type, so
 * an `.md`-renamed `.mp4` fails both gates (spec FR-12: no video binaries).
 */
#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
final class ReplaceMaterialData extends Data
{
    public function __construct(
        public UploadedFile $file,
        public ?string $title = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:20480', // 20 MB
                'extensions:md,markdown,pdf',
                'mimetypes:text/markdown,text/plain,text/x-markdown,application/pdf',
            ],
            'title' => ['nullable', 'string', 'max:255'],
        ];
    }
}
