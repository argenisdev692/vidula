<?php

declare(strict_types=1);

namespace Modules\Meeting\Application\DTOs;

use Shared\Application\DTOs\SoftDeleteFilterData;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * List filter for `GET /meetings` — mirrors `AppointmentFilterData` shape:
 * `status` (active|suspended, soft-delete) is inherited; `meeting_status` and
 * the `starts_from`/`starts_to` window are Meeting-specific.
 */
#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
final class MeetingFilterData extends SoftDeleteFilterData
{
    public function __construct(
        ?string $search = null,
        ?string $status = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        public ?string $meetingStatus = null,
        public ?string $startsFrom = null,
        public ?string $startsTo = null,
    ) {
        parent::__construct($search, $status, $dateFrom, $dateTo);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function rules(): array
    {
        return [
            ...self::baseRules(),
            'status' => ['nullable', 'string', 'in:active,suspended'],
            'meeting_status' => ['nullable', 'string', 'in:Scheduled,Cancelled'],
            'starts_from' => ['nullable', 'date'],
            'starts_to' => ['nullable', 'date', 'after_or_equal:starts_from'],
        ];
    }
}
