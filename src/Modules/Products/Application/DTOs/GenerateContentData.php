<?php

declare(strict_types=1);

namespace Modules\Products\Application\DTOs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Seed payload for one generation run. The controller accepts either a pasted
 * `markdown` field or an uploaded `.md` file and normalises both into this DTO,
 * so the Application layer never sees an UploadedFile.
 *
 * The byte cap is enforced again in StartContentGenerationHandler against
 * `config('products.generation.max_markdown_bytes')` — the rule below is the
 * cheap first gate (OWASP: unrestricted resource consumption).
 */
#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
final class GenerateContentData extends Data
{
    public function __construct(
        public string $markdown,
        public string $mode = 'replace',
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'markdown' => ['required', 'string', 'min:1', 'max:1048576'],
            'mode' => ['required', 'string', 'in:replace,force_replace'],
        ];
    }

    public function forcesReplaceOfVerifiedScripts(): bool
    {
        return $this->mode === 'force_replace';
    }
}
