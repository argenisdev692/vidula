<?php

declare(strict_types=1);

namespace Modules\Meeting\Application\DTOs;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * The shared read-model both the calendar's own meetings AND the read-only
 * Appointment overlay map into — the single shape `@fullcalendar/vue3`
 * consumes (plan.md §3/§5). `source` lets the frontend style/route the two
 * kinds of events differently without knowing their backend origin.
 */
#[MapOutputName(SnakeCaseMapper::class)]
final class CalendarEventData extends Data
{
    public function __construct(
        public string $uuid,
        public string $title,
        public string $start,
        public string $end,
        public string $source,
        public ?string $status,
        public string $url,
    ) {}
}
