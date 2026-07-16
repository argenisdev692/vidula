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
 * Resolves a meeting's attendees to `{name, email}` pairs — the shape both
 * the Google Calendar sync adapter (`addAttendee`) and the attendee-facing
 * Mailables need. Column-scoped per type (never a full eager-loaded
 * `attendable`), same data-minimization reasoning as
 * {@see AttendeeOptionMapper}.
 */
final readonly class AttendeeEmailResolver
{
    /**
     * @param  Collection<int, MeetingAttendeeEloquentModel>  $attendees
     * @return array<int, array{name: string, email: string}>
     */
    public static function resolve(Collection $attendees): array
    {
        $byType = $attendees->groupBy('attendable_type');

        return [
            ...self::contactsFor($byType->get(AttendeeType::User->value, collect()), User::class),
            ...self::contactsFor($byType->get(AttendeeType::Lead->value, collect()), AppointmentEloquentModel::class),
            ...self::contactsFor($byType->get(AttendeeType::Contact->value, collect()), ContactSupportEloquentModel::class),
        ];
    }

    /**
     * @param  Collection<int, MeetingAttendeeEloquentModel>  $rows
     * @param  class-string  $modelClass
     * @return array<int, array{name: string, email: string}>
     */
    private static function contactsFor(Collection $rows, string $modelClass): array
    {
        if ($rows->isEmpty()) {
            return [];
        }

        return $modelClass::query()
            ->whereIn('id', $rows->pluck('attendable_id'))
            ->get(['first_name', 'last_name', 'email'])
            ->map(fn ($model): array => [
                'name' => trim("{$model->first_name} {$model->last_name}"),
                'email' => $model->email,
            ])
            ->all();
    }
}
