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
use Modules\Products\Domain\Enums\GenerationStatus;
use Modules\Products\Domain\Ports\ContentGenerationRepositoryPort;
use Modules\Products\Domain\Ports\ZipPackagePort;
use Modules\Products\Infrastructure\Broadcasting\ProductContentGenerationProgress;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductEloquentModel;
use Shared\Domain\Ports\AuditPort;
use Shared\Domain\Ports\StoragePort;
use Throwable;

/**
 * Rebuilds the ZIP for an already completed generation (US-5 package action).
 */
#[Queue('default')]
#[Tries(1)]
#[Timeout(300)]
final class BuildProductZipJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $generationUuid,
        private readonly ?int $causerId = null,
    ) {}

    public function handle(
        ContentGenerationRepositoryPort $generations,
        ZipPackagePort $zip,
        StoragePort $storage,
        AuditPort $audit,
    ): void {
        $generation = $generations->findByUuid($this->generationUuid);

        if ($generation === null) {
            return;
        }

        $product = ProductEloquentModel::query()->find($generation->product_id);

        if ($product === null) {
            return;
        }

        $causer = $this->causerId !== null ? User::find($this->causerId) : null;
        $userId = $causer?->id ?? (int) $generation->user_id;

        try {
            $generations->update($generation, [
                'status' => GenerationStatus::Packaging->value,
                'progress' => GenerationStatus::Packaging->progress(),
            ]);

            event(new ProductContentGenerationProgress(
                $userId,
                $product->uuid,
                $generation->uuid,
                GenerationStatus::Packaging->value,
                'Rebuilding ZIP package…',
                GenerationStatus::Packaging->progress(),
            ));

            $document = $generations->courseDocument($product->uuid);
            $entries = [];

            if ($generation->md_path) {
                $entries[] = ['path' => 'course.md', 'contents' => $this->readStored($storage, $generation->md_path)];
            }
            if ($generation->pdf_path) {
                $entries[] = ['path' => 'course.pdf', 'contents' => $this->readStored($storage, $generation->pdf_path)];
            }

            foreach ($document->sessions as $session) {
                foreach ($session->topics as $topic) {
                    $entries[] = [
                        'path' => sprintf(
                            'scripts/session-%02d/%02d-%s.md',
                            $session->sessionNumber,
                            $topic->sortOrder,
                            $this->slugify($topic->title),
                        ),
                        'contents' => $this->topicScriptMarkdown($topic),
                    ];
                }
            }

            $localZip = $zip->build($entries);
            $remote = 'products/'.$product->uuid.'/packages/'.basename($localZip);
            $stored = $storage->putFromPath($remote, $localZip, 'private');
            @unlink($localZip);

            $generations->update($generation, [
                'status' => GenerationStatus::Completed->value,
                'progress' => 100,
                'zip_path' => $stored,
                'error' => null,
            ]);

            event(new ProductContentGenerationProgress(
                $userId,
                $product->uuid,
                $generation->uuid,
                GenerationStatus::Completed->value,
                'Package ready.',
                100,
            ));

            $audit->log(
                event: 'products.package.completed',
                subject: $generation->refresh(),
                properties: ['product_uuid' => $product->uuid, 'zip_path' => $stored],
                causer: $causer,
                logName: 'products',
            );
        } catch (Throwable $e) {
            Log::error('products.package.failed', [
                'uuid' => $this->generationUuid,
                'error' => $e->getMessage(),
            ]);

            $generations->update($generation, [
                'status' => GenerationStatus::Completed->value,
                'progress' => 100,
                'error' => mb_substr('Package failed: '.$e->getMessage(), 0, 2000),
            ]);
        }
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
}
