<?php

declare(strict_types=1);

namespace Modules\Meeting\Infrastructure\Attendees;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Appointment\Infrastructure\Persistence\Eloquent\Models\AppointmentEloquentModel;
use Modules\ContactSupport\Infrastructure\Persistence\Eloquent\Models\ContactSupportEloquentModel;
use Modules\Meeting\Domain\ValueObjects\AttendeeType;
use Modules\Meeting\Infrastructure\Persistence\Eloquent\Models\MeetingAttendeeEloquentModel;

/**
 * The reverse of {@see AttendeeResolver}: maps a meeting's already-persisted
 * attendees back to the minimal `{type, uuid, label}` shape the Edit form's
 * `AttendeePicker` expects. Deliberately does NOT eager-load the `attendable`
 * morph relation (that would return the FULL target row — e.g. a User row
 * includes `two_factor_secret`, which is not in `User::$hidden`). Instead it
 * runs one column-scoped query per attendee type, mirroring
 * `SearchAttendeesHandler`'s data-minimization (research.md §5 API3).
 */
final readonly class AttendeeOptionMapper
{
    /**
     * @param  Collection<int, MeetingAttendeeEloquentModel>  $attendees
     * @return array<int, array{type: string, uuid: string, label: string}>
     */
    public static function toOptions(Collection $attendees): array
    {
        $byType = $attendees->groupBy('attendable_type');

        $users = self::labelsFor($byType->get(AttendeeType::User->value, collect()), User::class, 'user');
        $leads = self::labelsFor($byType->get(AttendeeType::Lead->value, collect()), AppointmentEloquentModel::class, 'lead');
        $contacts = self::labelsFor($byType->get(AttendeeType::Contact->value, collect()), ContactSupportEloquentModel::class, 'contact');

        return [...$users, ...$leads, ...$contacts];
    }

    /**
     * @param  Collection<int, MeetingAttendeeEloquentModel>  $rows
     * @param  class-string  $modelClass
     * @return array<int, array{type: string, uuid: string, label: string}>
     */
    private static function labelsFor(Collection $rows, string $modelClass, string $type): array
    {
        if ($rows->isEmpty()) {
            return [];
        }

        return $modelClass::query()
            ->whereIn('id', $rows->pluck('attendable_id'))
            ->get(['uuid', 'first_name', 'last_name'])
            ->map(fn ($model): array => [
                'type' => $type,
                'uuid' => $model->uuid,
                'label' => trim("{$model->first_name} {$model->last_name}"),
            ])
            ->all();
    }
}
