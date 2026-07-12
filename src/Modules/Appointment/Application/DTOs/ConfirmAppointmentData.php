<?php

declare(strict_types=1);

namespace Modules\Appointment\Application\DTOs;

use Modules\Appointment\Domain\Services\AppointmentScheduler;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Admin action payload for confirming a lead's meeting. `scheduledAt` is
 * optional: omit it to confirm the already-requested time as-is, or supply a
 * new one to adjust the time while confirming in one step. Either way,
 * {@see AppointmentScheduler} re-validates
 * the final timestamp before the meeting is marked Confirmed.
 */
#[MapInputName(SnakeCaseMapper::class)]
final class ConfirmAppointmentData extends Data
{
    public function __construct(
        public ?string $scheduledAt = null,
    ) {}

    /**
     * @return array<string, array<int, string>>
     */
    public static function rules(): array
    {
        return [
            'scheduled_at' => ['nullable', 'date'],
        ];
    }
}
