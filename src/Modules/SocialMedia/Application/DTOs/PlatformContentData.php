<?php

declare(strict_types=1);

namespace Modules\SocialMedia\Application\DTOs;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * One platform's adapted copy + cover image + (TikTok-only) video script and
 * voiceover. `imagePath`/`voiceoverAudioPath` are R2 keys, filled in by
 * {@see \Modules\SocialMedia\Infrastructure\Ai\LaravelAiSocialMediaAssistantAdapter}
 * AFTER the text is generated — null when the caller opted out via
 * `generate_images` / `generate_voiceover` or the provider call failed.
 */
#[MapOutputName(SnakeCaseMapper::class)]
final class PlatformContentData extends Data
{
    /**
     * @param  list<string>  $hashtags
     * @param  list<string>  $threadTweets
     */
    public function __construct(
        public string $platform,
        public string $adaptedContent,
        public int $characterCount,
        public array $hashtags,
        public string $imagePrompt,
        public bool $isThread = false,
        public array $threadTweets = [],
        public ?string $videoScript = null,
        public ?string $imagePath = null,
        public ?string $imageUrl = null,
        public ?string $voiceoverAudioPath = null,
        public ?string $voiceoverAudioUrl = null,
    ) {}
}
