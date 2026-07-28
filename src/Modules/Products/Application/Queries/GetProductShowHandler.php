<?php

declare(strict_types=1);

namespace Modules\Products\Application\Queries;

use Modules\Products\Application\DTOs\CourseDocumentData;
use Modules\Products\Domain\Ports\ContentGenerationRepositoryPort;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductEloquentModel;

/**
 * Assembles the Show-page payload: catalog product + latest generation status
 * (without seed markdown) + a slim content tree (titles + script status only).
 *
 * Full script bodies stay behind GET /topics/{uuid}/script so Inertia props
 * stay small (Eloquent / Inertia best practice).
 */
final readonly class GetProductShowHandler
{
    public function __construct(
        private GetProductHandler $getProduct,
        private ContentGenerationRepositoryPort $generations,
        private GetContentGenerationStatusHandler $generationStatus,
    ) {}

    /**
     * @return array{
     *     product: ProductEloquentModel,
     *     generation: array{uuid: string, status: string, mode: string, progress: int, sessions_count: int, topics_count: int, scripts_count: int, error: string|null, started_at: string|null, completed_at: string|null, has_package: bool}|null,
     *     sessions: list<array{session_number: int, title: string, topics: list<array{uuid: string, title: string, sort_order: int, status: string|null, estimated_minutes: int|null}>}>
     * }
     */
    public function handle(string $uuid): array
    {
        $product = $this->getProduct->handle($uuid);

        $latest = $this->generations->findInFlightForProduct($product->id)
            ?? $this->generations->latestForProduct($product->id);

        $generation = $latest !== null
            ? $this->generationStatus->handle($product, $latest->uuid)
            : null;

        $document = $this->generations->courseDocument($product->uuid);

        return [
            'product' => $product,
            'generation' => $generation,
            'sessions' => $this->slimSessions($document),
        ];
    }

    /**
     * @return list<array{session_number: int, title: string, topics: list<array{uuid: string, title: string, sort_order: int, status: string|null, estimated_minutes: int|null}>}>
     */
    private function slimSessions(CourseDocumentData $document): array
    {
        $sessions = [];

        foreach ($document->sessions as $session) {
            $topics = [];

            foreach ($session->topics as $topic) {
                $topics[] = [
                    'uuid' => $topic->uuid,
                    'title' => $topic->title,
                    'sort_order' => $topic->sortOrder,
                    'status' => $topic->status?->value,
                    'estimated_minutes' => $topic->estimatedMinutes,
                ];
            }

            $sessions[] = [
                'session_number' => $session->sessionNumber,
                'title' => $session->title,
                'topics' => $topics,
            ];
        }

        return $sessions;
    }
}
