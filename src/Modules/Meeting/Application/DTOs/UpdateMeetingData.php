<?php

declare(strict_types=1);

namespace Modules\Meeting\Application\DTOs;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Same shape as {@see CreateMeetingData} — `organizer_id` stays immutable
 * after creation (never accepted from the client here either). `ends_at` is
 * derived server-side from `starts_at` + config duration.
 */
#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
final class UpdateMeetingData extends Data
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
