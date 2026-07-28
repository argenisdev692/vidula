<?php

declare(strict_types=1);

namespace Modules\Products\Infrastructure\Persistence\Repositories;

use Modules\Products\Application\DTOs\GeneratedTopicContentData;
use Modules\Products\Domain\Enums\ScriptStatus;
use Modules\Products\Domain\Ports\ProductScriptRepositoryPort;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductEloquentModel;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductScriptEloquentModel;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductSessionTopicEloquentModel;

final readonly class EloquentProductScriptRepository implements ProductScriptRepositoryPort
{
    public function findByTopicUuid(string $topicUuid): ?ProductScriptEloquentModel
    {
        return ProductScriptEloquentModel::query()
            ->whereHas('topic', fn ($q) => $q->where('uuid', $topicUuid))
            ->first();
    }

    public function findByTopicUuidForProduct(string $topicUuid, int $productId): ?ProductScriptEloquentModel
    {
        return ProductScriptEloquentModel::query()
            ->whereHas(
                'topic',
                fn ($q) => $q
                    ->where('uuid', $topicUuid)
                    ->whereHas('session', fn ($s) => $s->where('product_id', $productId)),
            )
            ->first();
    }

    public function upsert(int $topicId, array $attributes): ProductScriptEloquentModel
    {
        return ProductScriptEloquentModel::query()->updateOrCreate(
            ['product_session_topic_id' => $topicId],
            $attributes,
        );
    }

    public function update(
        ProductScriptEloquentModel $script,
        array $attributes,
    ): ProductScriptEloquentModel {
        $script->update($attributes);

        return $script->refresh();
    }

    public function saveGeneratedContent(
        string $topicUuid,
        GeneratedTopicContentData $content,
        bool $forceReplace = false,
    ): void {
        $topic = ProductSessionTopicEloquentModel::query()
            ->where('uuid', $topicUuid)
            ->with('script')
            ->first();

        if ($topic === null) {
            return;
        }

        $existing = $topic->script;

        if (! $forceReplace && $existing !== null && $this->isHumanApproved($existing)) {
            return;
        }

        $topic->update(['sources_json' => $content->sources]);

        $this->upsert($topic->id, [
            'intro' => $content->intro,
            'body' => $content->body,
            'outro' => $content->outro,
            'notes' => $content->notes,
            'status' => $content->status->value,
            'estimated_minutes' => $content->estimatedMinutes,
            'generated_by_model' => $content->model,
            'sources_json' => $content->sources,
        ]);
    }

    public function markNeedsReview(string $topicUuid, string $reason): void
    {
        $script = $this->findByTopicUuid($topicUuid);

        if ($script === null || $this->isHumanApproved($script)) {
            return;
        }

        $notes = trim(($script->notes ?? '')."\n\n[needs_review] ".$reason);

        $script->update([
            'status' => ScriptStatus::NeedsReview->value,
            'notes' => $notes,
        ]);
    }

    public function markTitlesNeedingReview(string $productUuid, array $titles, string $reason): int
    {
        if ($titles === []) {
            return 0;
        }

        $product = ProductEloquentModel::query()->where('uuid', $productUuid)->first();

        if ($product === null) {
            return 0;
        }

        $normalized = array_map(
            static fn (string $title): string => mb_strtolower(trim($title)),
            $titles,
        );

        $flagged = 0;

        $topics = ProductSessionTopicEloquentModel::query()
            ->whereHas('session', fn ($q) => $q->where('product_id', $product->id))
            ->with('script')
            ->get();

        foreach ($topics as $topic) {
            if (! in_array(mb_strtolower(trim($topic->title)), $normalized, true)) {
                continue;
            }

            if ($topic->script === null || $this->isHumanApproved($topic->script)) {
                continue;
            }

            $this->markNeedsReview($topic->uuid, $reason);
            $flagged++;
        }

        return $flagged;
    }

    private function isHumanApproved(ProductScriptEloquentModel $script): bool
    {
        $status = $script->status instanceof ScriptStatus
            ? $script->status
            : ScriptStatus::tryFrom((string) $script->status);

        return $status?->isHumanApproved() ?? false;
    }
}
