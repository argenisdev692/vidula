<?php

declare(strict_types=1);

namespace Modules\Portfolio\Application\DTOs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Create/update payload for a portfolio project. Store and Update share 100% of
 * the fields and rules (DTO Fusion Rule), so a single fused DTO is used.
 *
 * Cover and video arrive as R2 object keys after a prior browser → R2 PUT via
 * `POST /portfolios/uploads/presign` (StoragePort::temporaryUploadUrl). The
 * handlers verify prefix + existence before persisting. `remove_cover` /
 * `remove_video` clear a slot when no replacement key is sent (a new key always
 * wins — see UpdatePortfolioHandler).
 *
 * `tech_stack` is a JSON string list (e.g. React, Next.js, PostgreSQL) exposed
 * on the public Scramble feed for Astro badge rendering — max 20 × 50 chars.
 */
#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
final class PortfolioData extends Data
{
    /**
     * @param  list<string>  $techStack
     */
    public function __construct(
        public string $title,
        public string $clientName,
        public string $projectType,
        public array $techStack = [],
        public ?string $liveUrl = null,
        public ?string $publishedAt = null,
        public bool $isPublic = true,
        public ?string $description = null,
        public int $sortOrder = 0,
        public ?string $coverPath = null,
        public ?string $videoPath = null,
        public bool $removeCover = false,
        public bool $removeVideo = false,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        $coverPrefix = preg_quote(rtrim((string) config('portfolio.cover_prefix', 'portfolios/cover'), '/'), '/');
        $videoPrefix = preg_quote(rtrim((string) config('portfolio.video_prefix', 'portfolios/video'), '/'), '/');

        return [
            'title' => ['required', 'string', 'max:255'],
            'client_name' => ['required', 'string', 'max:255'],
            'project_type' => ['required', 'string', 'max:50'],
            'tech_stack' => ['array', 'max:20'],
            'tech_stack.*' => ['string', 'max:50'],
            'live_url' => ['nullable', 'url', 'max:500'],
            'published_at' => ['nullable', 'date'],
            'is_public' => ['boolean'],
            'description' => ['nullable', 'string', 'max:5000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'remove_cover' => ['boolean'],
            'remove_video' => ['boolean'],
            'cover_path' => [
                'nullable',
                'string',
                'max:500',
                'regex:/^'.$coverPrefix.'\/[0-9a-fA-F\-]{36}\/[\w.\-]+$/',
            ],
            'video_path' => [
                'nullable',
                'string',
                'max:500',
                'regex:/^'.$videoPrefix.'\/[0-9a-fA-F\-]{36}\/[\w.\-]+$/',
            ],
        ];
    }
}
