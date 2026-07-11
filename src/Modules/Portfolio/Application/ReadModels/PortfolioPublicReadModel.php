<?php

declare(strict_types=1);

namespace Modules\Portfolio\Application\ReadModels;

use Modules\Portfolio\Infrastructure\Persistence\Eloquent\Models\PortfolioEloquentModel;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Response shape for the anonymous landing-page feed. This is the property-level
 * authorization allowlist (OWASP §12): the public JSON is built ONLY from these
 * fields, so internal columns (`id`, `user_id`) and the authoring user never
 * leak — unlike serializing the Eloquent model directly and relying on `$hidden`.
 *
 * It also gives Scramble a precise response schema for `GET /api/portfolios/public`
 * instead of a generic paginator of loosely-typed models.
 *
 * Public URLs and `published_at` arrive already resolved/stringified (R2 public
 * URL via the model accessor, Carbon → ISO-8601), matching the BACKEND-PHP §5
 * date-handling rule (ReadModels carry strings, never Carbon).
 */
#[MapOutputName(SnakeCaseMapper::class)]
final class PortfolioPublicReadModel extends Data
{
    /**
     * @param  array<int, PortfolioGalleryImageReadModel>  $gallery
     */
    public function __construct(
        public string $uuid,
        public string $title,
        public string $clientName,
        public string $projectType,
        public ?string $liveUrl,
        public ?string $publishedAt,
        public ?string $description,
        public ?string $coverUrl,
        public ?string $videoUrl,
        public int $sortOrder,
        #[DataCollectionOf(PortfolioGalleryImageReadModel::class)]
        public array $gallery,
    ) {}

    public static function fromModel(PortfolioEloquentModel $model): self
    {
        return new self(
            uuid: $model->uuid,
            title: $model->title,
            clientName: $model->client_name,
            projectType: $model->project_type,
            liveUrl: $model->live_url,
            publishedAt: $model->published_at?->toIso8601String(),
            description: $model->description,
            coverUrl: $model->cover_url,
            videoUrl: $model->video_url,
            sortOrder: $model->sort_order,
            gallery: $model->gallery
                ->map(PortfolioGalleryImageReadModel::fromModel(...))
                ->all(),
        );
    }
}
