<?php

declare(strict_types=1);

namespace Modules\Products\Application\DTOs;

use Modules\Products\Domain\Enums\ProductType;
use Modules\Products\Domain\Ports\ProductContentGeneratorPort;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Everything {@see ProductContentGeneratorPort::generateTopic()} needs for ONE
 * topic. Deliberately free of client/student identifiers: no PII ever enters a
 * generation prompt (plan §8).
 */
#[MapOutputName(SnakeCaseMapper::class)]
final class TopicGenerationRequestData extends Data
{
    /**
     * @param  list<string>  $siblingTopicTitles  Other topics in the same session, so the model can avoid repeating them.
     */
    public function __construct(
        public string $productTitle,
        public ProductType $productType,
        public string $language,
        public int $sessionNumber,
        public string $sessionTitle,
        public string $topicTitle,
        public array $siblingTopicTitles = [],
        public ?string $topicDescription = null,
        public ?string $provider = null,
    ) {}
}
