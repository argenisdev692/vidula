<?php

declare(strict_types=1);

namespace Modules\Products\Domain\Ports;

use Modules\Products\Application\DTOs\ConsistencyReportData;
use Modules\Products\Application\DTOs\GeneratedTopicContentData;
use Modules\Products\Application\DTOs\TopicGenerationRequestData;

/**
 * AI generation for the content pipeline, scoped to ONE topic per call so a
 * 200-topic course degrades topic-by-topic instead of all-or-nothing: the job
 * owns the loop, the retry policy and the progress broadcast, this port owns
 * the research + prompt + model round-trip.
 *
 * Implementations MAY throw — the caller catches per topic and marks that
 * single script `needs_review` before moving on (spec FR-15).
 */
interface ProductContentGeneratorPort
{
    public function generateTopic(TopicGenerationRequestData $request): GeneratedTopicContentData;

    /**
     * Grades the generated titles against the operator's seed index.
     *
     * @param  list<string>  $seedTitles
     * @param  list<string>  $generatedTitles
     */
    public function verifyConsistency(string $productTitle, array $seedTitles, array $generatedTitles, ?string $provider = null): ConsistencyReportData;
}
