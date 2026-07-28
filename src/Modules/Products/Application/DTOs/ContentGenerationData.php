<?php

declare(strict_types=1);

namespace Modules\Products\Application\DTOs;

use Modules\Products\Domain\Enums\GenerationStatus;
use Modules\Products\Domain\Enums\ProductType;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Read model of one `content_generations` row plus the few product columns
 * the pipeline needs (type drives the parser + agent, language and title feed
 * the prompts). Denormalised on purpose so the job never has to reach for a
 * second repository just to know which agent to run.
 *
 * `sourceMarkdown` is operator-supplied text destined for an LLM prompt — it
 * is size-capped at request time and NEVER written to the activity log.
 */
#[MapOutputName(SnakeCaseMapper::class)]
final class ContentGenerationData extends Data
{
    public function __construct(
        public string $uuid,
        public string $productUuid,
        public string $productTitle,
        public ProductType $productType,
        public string $language,
        public GenerationStatus $status,
        public string $mode,
        public string $sourceMarkdown,
        public ?int $userId = null,
        public ?string $model = null,
        public ?string $productDescription = null,
    ) {}
}
