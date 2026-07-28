<?php

declare(strict_types=1);

namespace Modules\Products\Application\DTOs;

use Modules\Products\Domain\Enums\ProductType;
use Modules\Products\Domain\Ports\CourseRendererPort;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * The whole content tree flattened into what {@see CourseRendererPort} needs
 * to emit `course.md` / `course.pdf`. Built once per render so the renderers
 * stay pure functions of their input (no lazy relation loading mid-render).
 */
#[MapOutputName(SnakeCaseMapper::class)]
final class CourseDocumentData extends Data
{
    /**
     * @param  list<CourseSessionData>  $sessions
     */
    public function __construct(
        public string $productUuid,
        public string $title,
        public ProductType $type,
        public string $language,
        #[DataCollectionOf(CourseSessionData::class)]
        public array $sessions,
        public ?string $description = null,
    ) {}

    public function topicCount(): int
    {
        return array_sum(array_map(static fn (CourseSessionData $session): int => count($session->topics), $this->sessions));
    }
}
