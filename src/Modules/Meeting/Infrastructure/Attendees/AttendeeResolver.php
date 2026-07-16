<?php

declare(strict_types=1);

namespace Modules\Meeting\Infrastructure\Attendees;

use App\Models\User;
use Modules\Appointment\Infrastructure\Persistence\Eloquent\Models\AppointmentEloquentModel;
use Modules\ContactSupport\Infrastructure\Persistence\Eloquent\Models\ContactSupportEloquentModel;
use Modules\Meeting\Domain\Exceptions\AttendeeNotEligibleException;
use Modules\Meeting\Domain\ValueObjects\AttendeeType;

/**
 * Resolves a client-submitted `{type, uuid}` pair to the target row's internal
 * auto-increment id (the real `morphTo()` owner key, never the uuid — see
 * research.md §3) across the three eligible cross-module sources. The only
 * place Meeting reads User/Appointment/ContactSupport by uuid; kept in
 * Infrastructure (not Domain) since it queries Eloquent directly, same
 * pragmatic boundary as the existing `AppointmentCalendarFeedAdapter`.
 */
final readonly class AttendeeResolver
{
    /**
     * @return array{attendable_type: string, attendable_id: int}
     */
    public function resolve(string $type, string $uuid): array
    {
        $attendeeType = AttendeeType::tryFrom($type)
            ?? throw AttendeeNotEligibleException::forUuid($type, $uuid);

        $id = match ($attendeeType) {
            AttendeeType::User => User::query()->where('uuid', $uuid)->value('id'),
            AttendeeType::Lead => AppointmentEloquentModel::query()->where('uuid', $uuid)->value('id'),
            AttendeeType::Contact => ContactSupportEloquentModel::query()->where('uuid', $uuid)->value('id'),
        };

        if ($id === null) {
            throw AttendeeNotEligibleException::forUuid($type, $uuid);
        }

        return ['attendable_type' => $attendeeType->value, 'attendable_id' => (int) $id];
    }
}
