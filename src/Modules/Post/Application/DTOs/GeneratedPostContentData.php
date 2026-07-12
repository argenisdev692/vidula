<?php

declare(strict_types=1);

namespace Modules\Post\Application\DTOs;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Full generated draft returned to the frontend AI-assist panel — the user
 * reviews/edits this before it is ever persisted via CreatePostHandler /
 * UpdatePostHandler (PostData carries the final, possibly-edited values).
 */
#[MapOutputName(SnakeCaseMapper::class)]
final class GeneratedPostContentData extends Data
{
    public function __construct(
        public string $title,
        public string $content,
        public string $excerpt,
        public string $metaTitle,
        public string $metaDescription,
        public string $metaKeywords,
        public ?string $coverImagePath,
        public ?string $coverImageUrl,
        public string $provider,
        public int $seoScore,
        public int $eeatScore,
        public int $humanWritingIndex,
        public int $aiDetectionRisk,
        /** @var array<string, mixed> */
        public array $scores,
        /** @var list<string> */
        public array $optimizationSuggestions,
        /** @var array{primary_keyword: string, lsi_keywords: list<string>} */
        public array $seoAnalysis,
    ) {}
}
