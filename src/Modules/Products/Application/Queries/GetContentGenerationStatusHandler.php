<?php

declare(strict_types=1);

namespace Modules\Products\Application\Queries;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Products\Domain\Ports\ContentGenerationRepositoryPort;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ContentGenerationEloquentModel;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductEloquentModel;

/**
 * Progress poll for one run (spec US-8).
 *
 * The lookup is scoped to the product so a generation UUID from another
 * operator's product can never be read through this endpoint (OWASP API1 —
 * broken object level authorization). The returned payload deliberately omits
 * `source_markdown`: status screens do not need the seed back.
 */
final readonly class GetContentGenerationStatusHandler
{
    public function __construct(private ContentGenerationRepositoryPort $generations) {}

    /**
     * @return array{uuid: string, status: string, mode: string, progress: int, sessions_count: int, topics_count: int, scripts_count: int, error: string|null, started_at: string|null, completed_at: string|null, has_package: bool}
     */
    public function handle(ProductEloquentModel $product, string $generationUuid): array
    {
        $generation = $this->generations->findByUuidForProduct($generationUuid, $product->id)
            ?? throw (new ModelNotFoundException)->setModel(
                ContentGenerationEloquentModel::class,
                [$generationUuid],
            );

        return [
            'uuid' => $generation->uuid,
            'status' => $generation->status instanceof \BackedEnum
                ? (string) $generation->status->value
                : (string) $generation->status,
            'mode' => $generation->mode,
            'progress' => (int) $generation->progress,
            'sessions_count' => (int) $generation->sessions_count,
            'topics_count' => (int) $generation->topics_count,
            'scripts_count' => (int) $generation->scripts_count,
            'error' => $generation->error,
            'started_at' => $generation->started_at?->toIso8601String(),
            'completed_at' => $generation->completed_at?->toIso8601String(),
            'has_package' => $generation->zip_path !== null,
        ];
    }
}
