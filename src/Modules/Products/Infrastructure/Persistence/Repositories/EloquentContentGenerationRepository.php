<?php

declare(strict_types=1);

namespace Modules\Products\Infrastructure\Persistence\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Products\Application\DTOs\ContentTreeCountsData;
use Modules\Products\Application\DTOs\CourseDocumentData;
use Modules\Products\Application\DTOs\CourseSessionData;
use Modules\Products\Application\DTOs\CourseTopicData;
use Modules\Products\Application\DTOs\GenerationTopicData;
use Modules\Products\Application\DTOs\SeedOutlineData;
use Modules\Products\Domain\Enums\GenerationStatus;
use Modules\Products\Domain\Enums\ProductType;
use Modules\Products\Domain\Enums\ScriptStatus;
use Modules\Products\Domain\Ports\ContentGenerationRepositoryPort;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ContentGenerationEloquentModel;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductEloquentModel;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductScriptEloquentModel;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductSessionEloquentModel;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductSessionTopicEloquentModel;

final readonly class EloquentContentGenerationRepository implements ContentGenerationRepositoryPort
{
    public function create(array $attributes): ContentGenerationEloquentModel
    {
        return ContentGenerationEloquentModel::query()->create($attributes);
    }

    public function findByUuid(string $uuid): ?ContentGenerationEloquentModel
    {
        return ContentGenerationEloquentModel::query()
            ->where('uuid', $uuid)
            ->first();
    }

    public function findByUuidForProduct(string $uuid, int $productId): ?ContentGenerationEloquentModel
    {
        return ContentGenerationEloquentModel::query()
            ->where('uuid', $uuid)
            ->where('product_id', $productId)
            ->first();
    }

    public function findInFlightForProduct(int $productId): ?ContentGenerationEloquentModel
    {
        return ContentGenerationEloquentModel::query()
            ->where('product_id', $productId)
            ->whereIn('status', GenerationStatus::nonTerminalValues())
            ->orderByDesc('created_at')
            ->first();
    }

    public function hasInFlightFor(int $productId): bool
    {
        return $this->findInFlightForProduct($productId) !== null;
    }

    public function latestCompletedFor(int $productId): ?ContentGenerationEloquentModel
    {
        return ContentGenerationEloquentModel::query()
            ->where('product_id', $productId)
            ->where('status', GenerationStatus::Completed->value)
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->first();
    }

    public function latestForProduct(int $productId): ?ContentGenerationEloquentModel
    {
        return ContentGenerationEloquentModel::query()
            ->where('product_id', $productId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();
    }

    public function update(
        ContentGenerationEloquentModel $generation,
        array $attributes,
    ): ContentGenerationEloquentModel {
        $generation->update($attributes);

        return $generation->refresh();
    }

    public function replaceContentTree(
        string $productUuid,
        SeedOutlineData $outline,
        bool $forceReplace = false,
    ): ContentTreeCountsData {
        return DB::transaction(function () use ($productUuid, $outline, $forceReplace): ContentTreeCountsData {
            $product = ProductEloquentModel::query()->where('uuid', $productUuid)->firstOrFail();

            /** @var array<string, array{intro: ?string, body: ?string, outro: ?string, notes: ?string, status: string, estimated_minutes: ?int, generated_by_model: ?string, sources_json: mixed, topic_sources: mixed}> $preserved */
            $preserved = [];

            if (! $forceReplace) {
                $existingTopics = ProductSessionTopicEloquentModel::query()
                    ->whereHas('session', fn ($q) => $q->where('product_id', $product->id))
                    ->with('script')
                    ->get();

                foreach ($existingTopics as $topic) {
                    $script = $topic->script;
                    if ($script === null) {
                        continue;
                    }

                    $status = $script->status instanceof ScriptStatus
                        ? $script->status
                        : ScriptStatus::tryFrom((string) $script->status);

                    if ($status === null || ! $status->isHumanApproved()) {
                        continue;
                    }

                    $key = mb_strtolower(trim($topic->title));
                    $preserved[$key] = [
                        'intro' => $script->intro,
                        'body' => $script->body,
                        'outro' => $script->outro,
                        'notes' => $script->notes,
                        'status' => $status->value,
                        'estimated_minutes' => $script->estimated_minutes,
                        'generated_by_model' => $script->generated_by_model,
                        'sources_json' => $script->sources_json,
                        'topic_sources' => $topic->sources_json,
                    ];
                }
            }

            ProductSessionEloquentModel::query()
                ->where('product_id', $product->id)
                ->each(function (ProductSessionEloquentModel $session): void {
                    $session->topics()->each(function (ProductSessionTopicEloquentModel $topic): void {
                        $topic->script()?->delete();
                        $topic->delete();
                    });
                    $session->delete();
                });

            $sessionsCount = 0;
            $topicsCount = 0;
            $scriptsCount = 0;

            foreach ($outline->sessions as $seedSession) {
                $session = ProductSessionEloquentModel::query()->create([
                    'uuid' => (string) Str::uuid7(),
                    'product_id' => $product->id,
                    'session_number' => $seedSession->sessionNumber,
                    'title' => $seedSession->title,
                ]);
                $sessionsCount++;

                foreach ($seedSession->topics as $seedTopic) {
                    $key = mb_strtolower(trim($seedTopic->title));
                    $kept = $preserved[$key] ?? null;

                    $topic = ProductSessionTopicEloquentModel::query()->create([
                        'uuid' => (string) Str::uuid7(),
                        'product_session_id' => $session->id,
                        'title' => $seedTopic->title,
                        'sort_order' => $seedTopic->sortOrder,
                        'sources_json' => $kept['topic_sources'] ?? null,
                    ]);
                    $topicsCount++;

                    ProductScriptEloquentModel::query()->create([
                        'uuid' => (string) Str::uuid7(),
                        'product_session_topic_id' => $topic->id,
                        'intro' => $kept['intro'] ?? null,
                        'body' => $kept['body'] ?? null,
                        'outro' => $kept['outro'] ?? null,
                        'notes' => $kept['notes'] ?? null,
                        'status' => $kept['status'] ?? ScriptStatus::Draft->value,
                        'estimated_minutes' => $kept['estimated_minutes'] ?? null,
                        'generated_by_model' => $kept['generated_by_model'] ?? null,
                        'sources_json' => $kept['sources_json'] ?? null,
                    ]);
                    $scriptsCount++;
                }
            }

            return new ContentTreeCountsData($sessionsCount, $topicsCount, $scriptsCount);
        });
    }

    public function topicsForProduct(string $productUuid): array
    {
        $product = ProductEloquentModel::query()->where('uuid', $productUuid)->firstOrFail();

        $topics = [];

        $sessions = ProductSessionEloquentModel::query()
            ->where('product_id', $product->id)
            ->with(['topics' => fn ($q) => $q->orderBy('sort_order')])
            ->orderBy('session_number')
            ->get(['id', 'session_number', 'title']);

        foreach ($sessions as $session) {
            $siblingTitles = $session->topics->pluck('title')->all();

            foreach ($session->topics as $topic) {
                $topics[] = new GenerationTopicData(
                    uuid: $topic->uuid,
                    title: $topic->title,
                    sessionNumber: (int) $session->session_number,
                    sessionTitle: $session->title,
                    sortOrder: (int) $topic->sort_order,
                    description: $topic->description,
                );
            }

            unset($siblingTitles);
        }

        return $topics;
    }

    public function courseDocument(string $productUuid): CourseDocumentData
    {
        $product = ProductEloquentModel::query()
            ->where('uuid', $productUuid)
            ->with([
                'sessions' => fn ($q) => $q->orderBy('session_number'),
                'sessions.topics' => fn ($q) => $q->orderBy('sort_order'),
                'sessions.topics.script',
            ])
            ->firstOrFail();

        $sessions = [];

        foreach ($product->sessions as $session) {
            $topics = [];

            foreach ($session->topics as $topic) {
                $script = $topic->script;
                $topics[] = new CourseTopicData(
                    uuid: $topic->uuid,
                    title: $topic->title,
                    sortOrder: (int) $topic->sort_order,
                    intro: $script?->intro,
                    body: $script?->body,
                    outro: $script?->outro,
                    notes: $script?->notes,
                    estimatedMinutes: $script?->estimated_minutes,
                    status: $script?->status instanceof ScriptStatus
                        ? $script->status
                        : ($script?->status !== null ? ScriptStatus::tryFrom((string) $script->status) : null),
                    sources: is_array($topic->sources_json) ? $topic->sources_json : [],
                );
            }

            $sessions[] = new CourseSessionData(
                sessionNumber: (int) $session->session_number,
                title: $session->title,
                topics: $topics,
            );
        }

        $type = $product->type instanceof ProductType
            ? $product->type
            : ProductType::from((string) $product->type);

        return new CourseDocumentData(
            productUuid: $product->uuid,
            title: $product->title,
            type: $type,
            language: $product->language,
            sessions: $sessions,
            description: $product->description,
        );
    }
}
