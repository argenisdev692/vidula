<?php

declare(strict_types=1);

namespace Modules\Portfolio\Application\DTOs;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Metadata-only payload to mint a short-lived R2 PUT URL for portfolio cover
 * or showcase video. The binary never touches Laravel — only filename / MIME /
 * size are validated here (OWASP A01/A04 + BACKEND-PHP §5).
 */
#[MapInputName(SnakeCaseMapper::class)]
final class PresignPortfolioMediaData extends Data
{
    public function __construct(
        public string $kind,
        public string $filename,
        public string $contentType,
        public int $sizeBytes,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        $maxCover = (int) config('portfolio.max_cover_bytes', 4 * 1024 * 1024);
        $maxVideo = (int) config('portfolio.max_video_bytes', 50 * 1024 * 1024);
        $maxBytes = max($maxCover, $maxVideo);

        return [
            'kind' => ['required', 'string', Rule::in(['cover', 'video'])],
            'filename' => ['required', 'string', 'max:255'],
            'content_type' => [
                'required',
                'string',
                'max:128',
                Rule::in(['image/jpeg', 'image/png', 'image/webp', 'video/mp4', 'video/webm']),
            ],
            'size_bytes' => ['required', 'integer', 'min:1', 'max:'.$maxBytes],
        ];
    }
}
