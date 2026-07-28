<?php

declare(strict_types=1);

namespace Modules\Products\Domain\Ports;

use Modules\Products\Application\DTOs\GeneratedTopicContentData;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductScriptEloquentModel;

/**
 * Per-topic script reads/writes for human review and the generation pipeline.
 */
interface ProductScriptRepositoryPort
{
    public function findByTopicUuid(string $topicUuid): ?ProductScriptEloquentModel;

    /**
     * Product-scoped lookup so a topic UUID from another product cannot be
     * reached through a foreign product route (OWASP API1).
     */
    public function findByTopicUuidForProduct(string $topicUuid, int $productId): ?ProductScriptEloquentModel;

    /** @param  array<string, mixed>  $attributes */
    public function upsert(int $topicId, array $attributes): ProductScriptEloquentModel;

    /** @param  array<string, mixed>  $attributes */
    public function update(
        ProductScriptEloquentModel $script,
        array $attributes,
    ): ProductScriptEloquentModel;

    /**
     * Persist generated content. Unless `$forceReplace` is true, MUST NOT
     * overwrite human-approved (verified/recorded) scripts.
     */
    public function saveGeneratedContent(
        string $topicUuid,
        GeneratedTopicContentData $content,
        bool $forceReplace = false,
    ): void;

    public function markNeedsReview(string $topicUuid, string $reason): void;

    /**
     * @param  list<string>  $titles
     */
    public function markTitlesNeedingReview(string $productUuid, array $titles, string $reason): int;
}
