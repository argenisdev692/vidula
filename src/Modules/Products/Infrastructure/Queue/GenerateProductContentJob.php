<?php

declare(strict_types=1);

namespace Modules\Products\Infrastructure\Queue;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Products\Application\DTOs\GeneratedMaterialData;
use Modules\Products\Application\DTOs\GenerationTopicData;
use Modules\Products\Application\DTOs\TopicGenerationRequestData;
use Modules\Products\Application\Services\SeedOutlineParser;
use Modules\Products\Domain\Enums\GenerationStatus;
use Modules\Products\Domain\Enums\MaterialType;
use Modules\Products\Domain\Enums\ProductType;
use Modules\Products\Domain\Enums\ScriptStatus;
use Modules\Products\Domain\Ports\ContentGenerationRepositoryPort;
use Modules\Products\Domain\Ports\CourseRendererPort;
use Modules\Products\Domain\Ports\ProductContentGeneratorPort;
use Modules\Products\Domain\Ports\ProductMaterialRepositoryPort;
use Modules\Products\Domain\Ports\ProductScriptRepositoryPort;
use Modules\Products\Domain\Ports\ZipPackagePort;
use Modules\Products\Infrastructure\Broadcasting\ProductContentGenerationProgress;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ContentGenerationEloquentModel;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductEloquentModel;
use Shared\Domain\Ports\AuditPort;
use Shared\Domain\Ports\StoragePort;
use Throwable;

/**
 * Async pipeline: parse → generate topics → verify → render MD/PDF → ZIP.
 * Dependencies are method-injected (Campaigns pattern) so only scalars sit on $this.
 */
#[Queue('default')]
#[Tries(1)]
#[Timeout(900)]
final class GenerateProductContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $generationUuid,
        private readonly ?int $causerId = null,
    ) {}

    public function handle(
        ContentGenerationRepositoryPort $generations,
        ProductContentGeneratorPort $generator,
        ProductScriptRepositoryPort $scripts,
        ProductMaterialRepositoryPort $materials,
        CourseRendererPort $renderer,
        ZipPackagePort $zip,
        StoragePort $storage,
        SeedOutlineParser $parser,
        AuditPort $audit,
    ): void {
        $generation = $generations->findByUuid($this->generationUuid);

        if ($generation === null) {
            Log::warning('products.generation.missing', ['uuid' => $this->generationUuid]);

            return;
        }

        $product = ProductEloquentModel::query()->find($generation->product_id);

        if ($product === null) {
            Log::warning('products.generation.product_missing', ['uuid' => $this->generationUuid]);

            return;
        }

        $causer = $this->causerId !== null ? User::find($this->causerId) : null;
        $userId = $causer?->id ?? (int) $generation->user_id;

        try {
            $this->advance($generations, $generation, $product, $userId, GenerationStatus::Parsing, 'Parsing seed outline…');

            $type = $product->productType();
            $outline = $parser->parse($generation->source_markdown, $type);
            $forceReplace = $generation->mode === 'force_replace';
            $counts = $generations->replaceContentTree($product->uuid, $outline, $forceReplace);

            $generations->update($generation, [
                'sessions_count' => $counts->sessionsCount,
                'topics_count' => $counts->topicsCount,
                'scripts_count' => $counts->scriptsCount,
                'started_at' => $generation->started_at ?? now(),
                'model' => config('products.default_model'),
            ]);

            $this->advance($generations, $generation, $product, $userId, GenerationStatus::Generating, 'Generating topic scripts…');

            $topics = $generations->topicsForProduct($product->uuid);
            $total = max(count($topics), 1);
            $done = 0;

            foreach ($topics as $topic) {
                $this->generateOneTopic($generator, $scripts, $product, $type, $topics, $topic, $forceReplace);
                $done++;
                $progress = GenerationStatus::Generating->progress()
                    + (int) floor(($done / $total) * (GenerationStatus::Verifying->progress() - GenerationStatus::Generating->progress()));
                $this->broadcast($userId, $product->uuid, $generation->uuid, GenerationStatus::Generating->value, "Generated {$done}/{$total} topics", $progress);
            }

            $this->advance($generations, $generation, $product, $userId, GenerationStatus::Verifying, 'Verifying titles against seed…');

            $seedTitles = $outline->topicTitles();
            $generatedTitles = array_map(static fn (GenerationTopicData $t): string => $t->title, $topics);
            $report = $generator->verifyConsistency($product->title, $seedTitles, $generatedTitles);

            if (! $report->consistent) {
                $scripts->markTitlesNeedingReview(
                    $product->uuid,
                    $report->titlesNeedingReview(),
                    $report->summary !== '' ? $report->summary : 'Consistency check flagged drift.',
                );
            }

            $this->advance($generations, $generation, $product, $userId, GenerationStatus::Rendering, 'Rendering course.md / course.pdf…');
            $paths = $this->renderAndStore($generations, $materials, $renderer, $storage, $product, $generation);

            $this->advance($generations, $generation, $product, $userId, GenerationStatus::Packaging, 'Building ZIP package…');
            $zipPath = $this->package($zip, $storage, $product, $paths['md'], $paths['pdf'], $generations->courseDocument($product->uuid));

            $generations->update($generation, [
                'status' => GenerationStatus::Completed->value,
                'progress' => 100,
                'md_path' => $paths['md'],
                'pdf_path' => $paths['pdf'],
                'zip_path' => $zipPath,
                'completed_at' => now(),
                'error' => null,
            ]);

            $this->broadcast($userId, $product->uuid, $generation->uuid, GenerationStatus::Completed->value, 'Generation completed.', 100);

            $audit->log(
                event: 'products.generation.completed',
                subject: $generation->refresh(),
                properties: [
                    'product_uuid' => $product->uuid,
                    'sessions_count' => $counts->sessionsCount,
                    'topics_count' => $counts->topicsCount,
                    'scripts_count' => $counts->scriptsCount,
                ],
                causer: $causer,
                logName: 'products',
            );
        } catch (Throwable $e) {
            Log::error('products.generation.failed', [
                'uuid' => $this->generationUuid,
                'error' => $e->getMessage(),
            ]);

            $generations->update($generation, [
                'status' => GenerationStatus::Failed->value,
                'progress' => 100,
                'error' => mb_substr($e->getMessage(), 0, 2000),
                'completed_at' => now(),
            ]);

            $this->broadcast($userId, $product->uuid, $generation->uuid, GenerationStatus::Failed->value, 'Generation failed.', 100);

            $audit->log(
                event: 'products.generation.failed',
                subject: $generation->refresh(),
                properties: [
                    'product_uuid' => $product->uuid,
                    'error' => mb_substr($e->getMessage(), 0, 500),
                ],
                causer: $causer,
                logName: 'products',
            );
        }
    }

    /**
     * @param  list<GenerationTopicData>  $allTopics
     */
    private function generateOneTopic(
        ProductContentGeneratorPort $generator,
        ProductScriptRepositoryPort $scripts,
        ProductEloquentModel $product,
        ProductType $type,
        array $allTopics,
        GenerationTopicData $topic,
        bool $forceReplace = false,
    ): void {
        // Skip AI for scripts preserved as verified/recorded (clarify Q7).
        if (! $forceReplace) {
            $existing = $scripts->findByTopicUuid($topic->uuid);
            $status = $existing?->status;
            $approved = $status instanceof ScriptStatus
                ? $status->isHumanApproved()
                : ScriptStatus::tryFrom((string) ($status ?? ''))?->isHumanApproved();

            if ($approved === true) {
                return;
            }
        }

        $siblings = array_values(array_filter(
            array_map(static fn (GenerationTopicData $t): string => $t->title, $allTopics),
            static fn (string $title): bool => $title !== $topic->title,
        ));

        try {
            $content = $generator->generateTopic(new TopicGenerationRequestData(
                productTitle: $product->title,
                productType: $type,
                language: $product->language,
                sessionNumber: $topic->sessionNumber,
                sessionTitle: $topic->sessionTitle,
                topicTitle: $topic->title,
                siblingTopicTitles: $siblings,
                topicDescription: $topic->description,
            ));
            $scripts->saveGeneratedContent($topic->uuid, $content, $forceReplace);
        } catch (Throwable $e) {
            Log::warning('products.generation.topic_failed', [
                'topic_uuid' => $topic->uuid,
                'error' => $e->getMessage(),
            ]);
            $scripts->markNeedsReview($topic->uuid, 'Generation failed: '.mb_substr($e->getMessage(), 0, 300));
        }
    }

    /**
     * @return array{md: string, pdf: string}
     */
    private function renderAndStore(
        ContentGenerationRepositoryPort $generations,
        ProductMaterialRepositoryPort $materials,
        CourseRendererPort $renderer,
        StoragePort $storage,
        ProductEloquentModel $product,
        ContentGenerationEloquentModel $generation,
    ): array {
        $document = $generations->courseDocument($product->uuid);
        $md = $renderer->renderMarkdown($document);
        $pdf = $renderer->renderPdf($document);

        $base = 'products/'.$product->uuid.'/generations/'.$generation->uuid;
        $mdPath = $storage->put($base.'/course.md', $md, 'private');
        $pdfPath = $storage->put($base.'/course.pdf', $pdf, 'private');

        $materials->replaceGenerated($product->uuid, [
            new GeneratedMaterialData(
                title: 'Course Markdown',
                type: MaterialType::Markdown,
                disk: 'r2',
                path: $mdPath,
                originalName: 'course.md',
                mimeType: 'text/markdown',
                sizeBytes: strlen($md),
                sortOrder: 1,
            ),
            new GeneratedMaterialData(
                title: 'Course PDF',
                type: MaterialType::Pdf,
                disk: 'r2',
                path: $pdfPath,
                originalName: 'course.pdf',
                mimeType: 'application/pdf',
                sizeBytes: strlen($pdf),
                sortOrder: 2,
            ),
        ]);

        return ['md' => $mdPath, 'pdf' => $pdfPath];
    }

    private function package(
        ZipPackagePort $zip,
        StoragePort $storage,
        ProductEloquentModel $product,
        string $mdPath,
        string $pdfPath,
        mixed $document,
    ): string {
        $entries = [
            ['path' => 'course.md', 'contents' => $this->readStored($storage, $mdPath)],
            ['path' => 'course.pdf', 'contents' => $this->readStored($storage, $pdfPath)],
        ];

        foreach ($document->sessions as $session) {
            foreach ($session->topics as $topic) {
                $scriptMd = $this->topicScriptMarkdown($topic);
                $entries[] = [
                    'path' => sprintf(
                        'scripts/session-%02d/%02d-%s.md',
                        $session->sessionNumber,
                        $topic->sortOrder,
                        $this->slugify($topic->title),
                    ),
                    'contents' => $scriptMd,
                ];
            }
        }

        $localZip = $zip->build($entries);
        $remote = 'products/'.$product->uuid.'/packages/'.basename($localZip);
        $stored = $storage->putFromPath($remote, $localZip, 'private');
        @unlink($localZip);

        return $stored;
    }

    private function readStored(StoragePort $storage, string $path): string
    {
        $local = tempnam(sys_get_temp_dir(), 'product-file-');

        if ($local === false) {
            return '';
        }

        $storage->copyToLocal($path, $local);
        $contents = (string) file_get_contents($local);
        @unlink($local);

        return $contents;
    }

    private function topicScriptMarkdown(object $topic): string
    {
        $parts = ['# '.$topic->title, ''];

        if ($topic->intro) {
            $parts[] = "## Intro\n".$topic->intro."\n";
        }
        if ($topic->body) {
            $parts[] = "## Body\n".$topic->body."\n";
        }
        if ($topic->outro) {
            $parts[] = "## Outro\n".$topic->outro."\n";
        }
        if ($topic->notes) {
            $parts[] = "## Notes\n".$topic->notes."\n";
        }

        return implode("\n", $parts);
    }

    private function slugify(string $title): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title) ?? ''));

        return trim($slug, '-') !== '' ? trim($slug, '-') : 'topic';
    }

    private function advance(
        ContentGenerationRepositoryPort $generations,
        ContentGenerationEloquentModel $generation,
        ProductEloquentModel $product,
        int $userId,
        GenerationStatus $status,
        string $message,
    ): void {
        $generations->update($generation, [
            'status' => $status->value,
            'progress' => $status->progress(),
        ]);
        $this->broadcast($userId, $product->uuid, $generation->uuid, $status->value, $message, $status->progress());
    }

    private function broadcast(
        int $userId,
        string $productUuid,
        string $generationUuid,
        string $stage,
        string $message,
        int $progress,
    ): void {
        if ($userId <= 0) {
            return;
        }

        event(new ProductContentGenerationProgress(
            $userId,
            $productUuid,
            $generationUuid,
            $stage,
            $message,
            $progress,
        ));
    }
}
