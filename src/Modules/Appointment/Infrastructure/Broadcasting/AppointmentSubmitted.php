<?php

declare(strict_types=1);

namespace Modules\Appointment\Infrastructure\Broadcasting;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Modules\Appointment\Infrastructure\Persistence\Eloquent\Models\AppointmentEloquentModel;

/**
 * New-booking ping for the navbar notification bell. Unlike the per-user
 * AI-progress broadcasts ({@see \Modules\Post\Infrastructure\Broadcasting\PostAiGenerationProgress}),
 * this is queued (`ShouldBroadcast`, not `ShouldBroadcastNow`) since it fires
 * from the PUBLIC booking form — the visitor's response must never wait on
 * Reverb delivery. Sent on the shared `notifications.appointments` channel
 * (permission-gated in routes/channels.php) rather than a single user's
 * channel, since any staff member with VIEW_ANY_APPOINTMENTS should see it,
 * not just one causer. Mirrors {@see \Modules\ContactSupport\Infrastructure\Broadcasting\ContactSupportSubmitted}.
 */
final class AppointmentSubmitted implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(
        private readonly AppointmentEloquentModel $appointment,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('notifications.appointments')];
    }

    public function broadcastAs(): string
    {
        return 'appointment.created';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'uuid' => $this->appointment->uuid,
            'created_at' => $this->appointment->created_at?->toIso8601String(),
        ];
    }
}
