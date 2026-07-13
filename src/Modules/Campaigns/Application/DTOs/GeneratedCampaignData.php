<?php

declare(strict_types=1);

namespace Modules\Campaigns\Application\DTOs;

use Modules\Campaigns\Domain\Ports\CampaignGeneratorPort;
use Modules\Campaigns\Infrastructure\Queue\GenerateCampaignJob;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * One full generation attempt returned by
 * {@see CampaignGeneratorPort}.
 * Iteration-level metadata (how many attempts it took, whether the loop gave
 * up) is NOT part of this shape —
 * {@see GenerateCampaignJob} adds it
 * once the loop finishes, keeping this DTO focused on "what the model
 * produced this one time".
 */
#[MapOutputName(SnakeCaseMapper::class)]
final class GeneratedCampaignData extends Data
{
    /**
     * @param  list<string>  $hashtags
     * @param  list<string>  $leadFormQuestions
     * @param  list<string>  $targetingSuggestions
     * @param  array<string, PlatformCampaignContentData>  $platforms
     * @param  list<string>  $optimizationSuggestions
     * @param  list<array{source: string, relevance: string, key_insight: string, used_in: list<string>}>  $researchSources
     * @param  list<string>  $tavilyDataUsed
     * @param  array{value: int, label: string, explanation: string}  $aiDetectionRisk
     */
    public function __construct(
        public string $headline,
        public string $primaryText,
        public ?string $description,
        public string $callToAction,
        public array $hashtags,
        public array $leadFormQuestions,
        public array $targetingSuggestions,
        public array $platforms,
        public ?string $coverImagePath,
        public ?string $coverImageUrl,
        public string $coverImagePrompt,
        public CampaignScoreSetData $scores,
        public array $optimizationSuggestions,
        public array $researchSources,
        public array $tavilyDataUsed,
        public array $aiDetectionRisk,
        public string $provider,
    ) {}
}
