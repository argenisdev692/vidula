<?php

declare(strict_types=1);

namespace Modules\Portfolio\Application\DTOs;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Create/update payload for a portfolio project. Store and Update share 100% of
 * the fields and rules (DTO Fusion Rule), so a single fused DTO is used: `cover`
 * and `video` are optional on both operations — on create they seed the columns,
 * on update they replace the R2 object when a new file is uploaded, or clear it
 * when `remove_cover` / `remove_video` is sent without a replacement file (a new
 * upload always takes precedence over a remove flag — see UpdatePortfolioHandler).
 *
 * Both media fields are validated (MIME + extension + magic bytes + size) before
 * they reach R2, then stored with public visibility so the landing page gallery
 * can render them via a permanent URL (BACKEND-PHP §5 public-asset exception —
 * the same convention already used by `BlogCategoryData->image` and the company
 * branding assets — never used for private user uploads).
 */
#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
final class PortfolioData extends Data
{
    public function __construct(
        public string $title,
        public string $clientName,
        public string $projectType,
        public ?string $liveUrl = null,
        public ?string $publishedAt = null,
        public bool $isPublic = true,
        public ?string $description = null,
        public int $sortOrder = 0,
        public ?UploadedFile $cover = null,
        public ?UploadedFile $video = null,
        public bool $removeCover = false,
        public bool $removeVideo = false,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'client_name' => ['required', 'string', 'max:255'],
            'project_type' => ['required', 'string', 'max:50'],
            'live_url' => ['nullable', 'url', 'max:500'],
            'published_at' => ['nullable', 'date'],
            'is_public' => ['boolean'],
            'description' => ['nullable', 'string', 'max:5000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'remove_cover' => ['boolean'],
            'remove_video' => ['boolean'],
            'cover' => [
                'nullable',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:4096', // 4 MB
                'dimensions:max_width=4096,max_height=4096',
            ],
            'video' => [
                'nullable',
                'file',
                'mimes:mp4,webm',
                'mimetypes:video/mp4,video/webm',
                'max:51200', // 50 MB
            ],
        ];
    }
}
