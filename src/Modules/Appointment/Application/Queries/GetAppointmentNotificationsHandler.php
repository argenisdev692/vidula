<?php

declare(strict_types=1);

namespace Modules\Appointment\Application\Queries;

use Illuminate\Support\Collection;
use Modules\Appointment\Domain\Ports\AppointmentRepositoryPort;
use Modules\Appointment\Infrastructure\Persistence\Eloquent\Models\AppointmentEloquentModel;

/**
 * Feed for the navbar notification bell: the unread count plus the most
 * recent leads (read or not), so the dropdown still shows recent activity
 * after everything has been read.
 */
final readonly class GetAppointmentNotificationsHandler
{
    private const int RECENT_LIMIT = 8;

    public function __construct(private AppointmentRepositoryPort $appointments) {}

    /**
     * @return array{unread_count: int, items: Collection<int, AppointmentEloquentModel>}
     */
    public function handle(): array
    {
        return [
            'unread_count' => $this->appointments->countUnread(),
            'items' => $this->appointments->recent(self::RECENT_LIMIT),
        ];
    }
}
