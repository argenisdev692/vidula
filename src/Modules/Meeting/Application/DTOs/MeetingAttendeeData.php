<?php

declare(strict_types=1);

namespace Modules\Meeting\Application\DTOs;

use Illuminate\Validation\Rule;
use Modules\Meeting\Domain\ValueObjects\AttendeeType;
use Spatie\LaravelData\Data;

/**
 * A single attendee reference in the create/update payload — a flat
 * `{type, uuid}` discriminator pair, not a polymorphic-cast object
 * (`spatie/laravel-data` has no first-class polymorphic support, confirmed in
 * research.md §4). `SearchAttendeesHandler` is the only place a client learns
 * a valid `uuid` for a given `type`.
 */
final class MeetingAttendeeData extends Data
{
    public function __construct(
        public string $type,
        public string $uuid,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'type' => ['required', Rule::in(array_map(fn (AttendeeType $t): string => $t->value, AttendeeType::cases()))],
            'uuid' => ['required', 'string', 'uuid'],
        ];
    }
}
