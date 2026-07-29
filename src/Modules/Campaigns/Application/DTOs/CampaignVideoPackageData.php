<?php

declare(strict_types=1);

namespace Modules\Campaigns\Application\DTOs;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * CapCut-ready Meta Reels/Stories video package (9:16, stage-aware 15–30s).
 * Nested under a platform variant when {@see CampaignAdFormat} is reel or story.
 */
#[MapOutputName(SnakeCaseMapper::class)]
final class CampaignVideoPackageData extends Data
{
    /**
     * @param  list<CampaignVideoSceneData>  $scenes
     */
    public function __construct(
        public array $scenes,
        public string $cleanScript,
        public string $soundSuggestion,
        public int $targetDurationSeconds = 15,
        public string $creativeStyle = 'ugc_native',
    ) {}
}
