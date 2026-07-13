<?php

declare(strict_types=1);

namespace Modules\SocialMedia\Application\DTOs;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * One full generation attempt returned by
 * {@see \Modules\SocialMedia\Domain\Ports\SocialMediaContentGeneratorPort}.
 * Iteration-level metadata (how many attempts it took, whether the loop gave
 * up) is NOT part of this shape — {@see \Modules\SocialMedia\Infrastructure\Queue\GenerateSocialMediaContentJob}
 * adds it once the loop finishes, keeping this DTO focused on "what the model
 * produced this one time".
 */
#[MapOutputName(SnakeCaseMapper::class)]
final class GeneratedSocialMediaContentData extends Data
{
    /**
     * @param  list<string>  $hashtags
     * @param  array<string, PlatformContentData>  $platforms
     * @param  array{experience_signals: list<string>, expertise_signals: list<string>, authoritativeness_signals: list<string>, trustworthiness_signals: list<string>}  $eeatAnalysis
     * @param  list<string>  $optimizationSuggestions
     * @param  list<array{source: string, relevance: string, key_insight: string, used_in: list<string>}>  $researchSources
     * @param  list<string>  $tavilyDataUsed
     * @param  array{value: int, label: string, explanation: string}  $aiDetectionRisk
     */
    public function __construct(
        public string $headline,
        public string $body,
        public string $callToAction,
        public array $hashtags,
        public array $platforms,
        public ?string $coverImagePath,
        public ?string $coverImageUrl,
        public string $coverImagePrompt,
        public ScoreSetData $scores,
        public array $eeatAnalysis,
        public array $optimizationSuggestions,
        public array $researchSources,
        public array $tavilyDataUsed,
        public array $aiDetectionRisk,
        public string $provider,
    ) {}
}
