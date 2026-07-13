<?php

declare(strict_types=1);

namespace Modules\ContactSupport\Infrastructure\Broadcasting;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Modules\ContactSupport\Infrastructure\Persistence\Eloquent\Models\ContactSupportEloquentModel;

/**
 * New-submission ping for the navbar notification bell. Unlike the per-user
 * AI-progress broadcasts ({@see \Modules\Post\Infrastructure\Broadcasting\PostAiGenerationProgress}),
 * this is queued (`ShouldBroadcast`, not `ShouldBroadcastNow`) since it fires
 * from the PUBLIC guest contact form — the visitor's response must never wait
 * on Reverb delivery. Sent on the shared `notifications.contact-supports`
 * channel (permission-gated in routes/channels.php) rather than a single
 * user's channel, since any staff member with VIEW_ANY_CONTACT_SUPPORTS
 * should see it, not just one causer.
 */
final class ContactSupportSubmitted implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(
        private readonly ContactSupportEloquentModel $contactSupport,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('notifications.contact-supports')];
    }

    public function broadcastAs(): string
    {
        return 'contact-support.created';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'uuid' => $this->contactSupport->uuid,
            'created_at' => $this->contactSupport->created_at?->toIso8601String(),
        ];
    }
}
