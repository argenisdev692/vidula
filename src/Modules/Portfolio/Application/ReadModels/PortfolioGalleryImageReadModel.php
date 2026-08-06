<?php

declare(strict_types=1);

namespace Modules\Portfolio\Application\ReadModels;

use Modules\Portfolio\Infrastructure\Persistence\Eloquent\Models\PortfolioMediaEloquentModel;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * One gallery image as exposed on the public landing-page feed. Allowlist by
 * construction (OWASP §12): only the resolved public `url` and its display
 * order leave the boundary — never the raw R2 object key (`path`) or the
 * internal integer `id`.
 */
#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
final class PortfolioGalleryImageReadModel extends Data
{
    public function __construct(
        public string $uuid,
        public ?string $url,
        public int $sortOrder,
    ) {}

    public static function fromModel(PortfolioMediaEloquentModel $media): self
    {
        return new self(
            uuid: $media->uuid,
            url: $media->url,
            sortOrder: $media->sort_order,
        );
    }
}
