<?php

declare(strict_types=1);

namespace Modules\Meeting\Application\DTOs;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Admin-created internal meeting. `organizer_id` is never accepted here — the
 * handler sets it from the authenticated user (OWASP API3, research.md §5).
 * `ends_at` is never accepted from the client — the handler derives it from
 * `starts_at` + `config('meeting.duration_minutes')`.
 *
 * Attendees use a plain `array` (not `DataCollection`) so handlers can iterate
 * without Spatie's transform pipeline TypeErroring on raw request arrays.
 */
#[MapInputName(SnakeCaseMapper::class)]
final class CreateMeetingData extends Data
{
    /**
     * @param  list<MeetingAttendeeData>  $attendees
     */
    public function __construct(
        public string $title,
        public ?string $description,
        public string $startsAt,
        #[DataCollectionOf(MeetingAttendeeData::class)]
        public array $attendees,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'starts_at' => ['required', 'date'],
            'attendees' => ['present', 'array', 'max:100'],
            'attendees.*.type' => ['required', 'string'],
            'attendees.*.uuid' => ['required', 'string', 'uuid'],
        ];
    }
}
