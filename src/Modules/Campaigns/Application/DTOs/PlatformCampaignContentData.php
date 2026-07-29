<?php

declare(strict_types=1);

namespace Modules\Campaigns\Application\DTOs;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * One Meta surface's (Facebook or Instagram) adapted ad copy + cover image.
 * Optional {@see CampaignVideoPackageData} is filled when the campaign
 * `ad_format` is reel or story. `imagePath`/`imageUrl` are filled in by the
 * Infrastructure AI adapter AFTER the text is generated — null when the
 * caller opted out via `generate_images` or the provider call failed.
 */
#[MapOutputName(SnakeCaseMapper::class)]
final class PlatformCampaignContentData extends Data
{
    /**
     * @param  list<string>  $hashtags
     */
    public function __construct(
        public string $platform,
        public string $adaptedPrimaryText,
        public int $characterCount,
        public string $headline,
        public ?string $description,
        public array $hashtags,
        public string $imagePrompt,
        public ?string $imagePath = null,
        public ?string $imageUrl = null,
        public ?CampaignVideoPackageData $videoPackage = null,
    ) {}
}
