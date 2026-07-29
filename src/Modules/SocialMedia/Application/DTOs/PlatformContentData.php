<?php

declare(strict_types=1);

namespace Modules\SocialMedia\Application\DTOs;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * One platform's adapted copy + cover image + (TikTok / Instagram Reels)
 * CapCut video package and optional voiceover. `imagePath` /
 * `voiceoverAudioPath` are R2 keys filled after text generation — null when
 * the caller opted out or the provider call failed.
 *
 * `imageRoute` is the Argenis visual path: `a` title+emblem, `b` abstract no
 * text, `c` SVG roadmap (labels rendered in PHP, not Imagen).
 * `videoScript` remains the clean VO string for backward-compat / TTS.
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
        public string $imageRoute = 'a',
        public bool $isThread = false,
        public array $threadTweets = [],
        public ?string $videoScript = null,
        public ?VideoPackageData $videoPackage = null,
        public ?string $imagePath = null,
        public ?string $imageUrl = null,
        public ?string $voiceoverAudioPath = null,
        public ?string $voiceoverAudioUrl = null,
    ) {}
}
