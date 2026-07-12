<?php

declare(strict_types=1);

namespace Modules\Appointment\Application\DTOs;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
final class RescheduleAppointmentData extends Data
{
    public function __construct(
        public string $scheduledAt,
    ) {}

    /**
     * @return array<string, array<int, string>>
     */
    public static function rules(): array
    {
        return [
            'scheduled_at' => ['required', 'date'],
        ];
    }
}
