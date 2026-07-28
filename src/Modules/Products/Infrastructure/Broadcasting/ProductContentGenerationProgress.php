<?php

declare(strict_types=1);

namespace Modules\Products\Infrastructure\Broadcasting;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/**
 * Real-time progress tick for GenerateProductContentJob. Broadcast immediately
 * (no extra queue hop) — mirrors CampaignAiGenerationProgress.
 */
final readonly class ProductContentGenerationProgress implements ShouldBroadcastNow
{
    public function __construct(
        private int $userId,
        private string $productUuid,
        private string $generationUuid,
        private string $stage,
        private string $message,
        private int $progress,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('App.Models.User.'.$this->userId)];
    }

    public function broadcastAs(): string
    {
        return 'products.generation.progress';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'product_uuid' => $this->productUuid,
            'generation_uuid' => $this->generationUuid,
            'stage' => $this->stage,
            'message' => $this->message,
            'progress' => $this->progress,
        ];
    }
}
