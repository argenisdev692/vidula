<?php

declare(strict_types=1);

namespace Modules\Products\Application\DTOs;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * What the `parsing` stage actually persisted — surfaced on the generation
 * record so the UI can show "8 sessions · 32 topics · 32 scripts".
 */
#[MapOutputName(SnakeCaseMapper::class)]
final class ContentTreeCountsData extends Data
{
    public function __construct(
        public int $sessionsCount,
        public int $topicsCount,
        public int $scriptsCount,
    ) {}
}
