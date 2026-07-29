<?php

declare(strict_types=1);

namespace Modules\SocialMedia\Domain\Ports;

use Modules\SocialMedia\Application\DTOs\GeneratedSocialMediaContentData;
use Modules\SocialMedia\Application\DTOs\GenerateSocialMediaContentData;
use Modules\SocialMedia\Domain\Services\ContentQualityEvaluator;

/**
 * Step 2: one generation attempt (fresh Tavily research + all 5 platforms +
 * scoring). Read-only with respect to the aggregate — the caller (the
 * quality-loop Job) decides whether to iterate again or persist the result.
 *
 * `$iteration` and `$previousWeaknesses` are null/empty on the first call;
 * from iteration 2 onward the job passes back
 * {@see ContentQualityEvaluator::identifyWeaknesses()}
 * so the agent targets the specific scores that failed instead of a blind
 * retry.
 */
interface SocialMediaContentGeneratorPort
{
    /**
     * @param  list<array{score: string, current: int, target: int, gap: int, explanation: string}>  $previousWeaknesses
     */
    public function generate(
        string $contentUuid,
        GenerateSocialMediaContentData $data,
        int $iteration = 1,
        array $previousWeaknesses = [],
        ?object $causer = null,
    ): GeneratedSocialMediaContentData;
}
