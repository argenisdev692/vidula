<?php

declare(strict_types=1);

namespace Modules\Campaigns\Domain\Ports;

use Modules\Campaigns\Application\DTOs\GenerateCampaignData;
use Modules\Campaigns\Application\DTOs\GeneratedCampaignData;
use Modules\Campaigns\Domain\Services\CampaignQualityEvaluator;
use Modules\Campaigns\Infrastructure\Queue\GenerateCampaignJob;

/**
 * Step 2: one generation attempt (fresh Tavily research + Facebook/Instagram
 * copy + scoring). Read-only with respect to the aggregate — the caller (the
 * quality-loop Job) decides whether to iterate again or persist the result.
 * Exactly ONE attempt per call — the up-to-5-iteration loop is orchestrated
 * by {@see GenerateCampaignJob}, not
 * here; keeping the loop out of the port/adapter is what lets the adapter
 * stay a thin, swappable AI client.
 *
 * `$iteration` and `$previousWeaknesses` are null/empty on the first call;
 * from iteration 2 onward the job passes back
 * {@see CampaignQualityEvaluator::identifyWeaknesses()}
 * so the agent targets the specific scores that failed instead of a blind
 * retry.
 */
interface CampaignGeneratorPort
{
    /**
     * @param  list<array{score: string, current: int, target: int, gap: int, explanation: string}>  $previousWeaknesses
     */
    public function generate(
        string $campaignUuid,
        GenerateCampaignData $data,
        int $iteration = 1,
        array $previousWeaknesses = [],
        ?object $causer = null,
    ): GeneratedCampaignData;
}
