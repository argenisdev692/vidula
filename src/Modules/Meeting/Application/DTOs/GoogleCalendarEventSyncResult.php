<?php

declare(strict_types=1);

namespace Modules\Meeting\Application\DTOs;

final readonly class GoogleCalendarEventSyncResult
{
    public function __construct(
        public string $eventId,
        public ?string $meetLink = null,
    ) {}
}
