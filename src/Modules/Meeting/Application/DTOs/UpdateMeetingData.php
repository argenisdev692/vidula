<?php

declare(strict_types=1);

namespace Modules\Meeting\Application\DTOs;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Same shape as {@see CreateMeetingData} — `organizer_id` stays immutable
 * after creation (never accepted from the client here either).
 */
#[MapInputName(SnakeCaseMapper::class)]
final class UpdateMeetingData extends Data
{
    /**
     * @param  DataCollection<int, MeetingAttendeeData>  $attendees
     */
    public function __construct(
        public string $title,
        public ?string $description,
        public string $startsAt,
        public string $endsAt,
        #[DataCollectionOf(MeetingAttendeeData::class)]
        public DataCollection $attendees,
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
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'attendees' => ['present', 'array', 'max:100'],
        ];
    }
}
