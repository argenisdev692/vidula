<?php

declare(strict_types=1);

namespace Modules\Services\Application\ReadModels;

use Modules\Services\Infrastructure\Persistence\Eloquent\Models\ServiceEloquentModel;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Response shape for the anonymous landing-page service catalog. Property-level
 * authorization allowlist (OWASP §12): the public JSON is built ONLY from these
 * fields, so internal columns (`id`, `user_id`, timestamps) never leak.
 *
 * Gives Scramble a precise schema for `GET /api/services/public` instead of
 * inferring the full {@see ServiceEloquentModel} (which includes admin-only fields).
 */
#[MapOutputName(SnakeCaseMapper::class)]
final class ServicePublicReadModel extends Data
{
    public function __construct(
        public string $uuid,
        public string $name,
        public string $slug,
        public ?string $description,
        public int $sortOrder,
    ) {}

    public static function fromModel(ServiceEloquentModel $model): self
    {
        return new self(
            uuid: $model->uuid,
            name: $model->name,
            slug: $model->slug,
            description: $model->description,
            sortOrder: $model->sort_order,
        );
    }
}
