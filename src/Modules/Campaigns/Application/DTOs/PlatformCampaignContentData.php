<?php

declare(strict_types=1);

namespace Modules\Campaigns\Application\DTOs;

use Modules\Campaigns\Infrastructure\Ai\LaravelAiCampaignAssistantAdapter;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * One Meta surface's (Facebook or Instagram) adapted ad copy + cover image.
 * `imagePath`/`imageUrl` are filled in by
 * {@see LaravelAiCampaignAssistantAdapter}
 * AFTER the text is generated — null when the caller opted out via
 * `generate_images` or the provider call failed.
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
    ) {}
}
