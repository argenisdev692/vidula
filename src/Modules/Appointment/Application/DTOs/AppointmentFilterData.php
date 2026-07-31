<?php

declare(strict_types=1);

namespace Modules\Appointment\Application\DTOs;

use Shared\Application\DTOs\SoftDeleteFilterData;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Shared list filter — consumed by ListAppointmentsHandler via the single
 * `AppointmentEloquentModel::scopeApplyFilters()` (BACKEND-PHP §4.1). Inherits
 * search/status/date from {@see SoftDeleteFilterData} and adds the CRM-specific
 * pipeline filters.
 *
 * - `status`: active | suspended (soft-deleted).
 * - `read`:   read | unread (the `readed` column).
 * - `spam`:   spam | ham (the `is_spam` verdict from the public booking form).
 * - `date_from` / `date_to` (inherited): window over `created_at` (when the
 *   lead was captured).
 * - `scheduled_from` / `scheduled_to`: separate window over `scheduled_at`
 *   (when the meeting itself is/was booked) — the two ranges are independent
 *   and both optional.
 */
#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
final class AppointmentFilterData extends SoftDeleteFilterData
{
    public function __construct(
        ?string $search = null,
        ?string $status = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        public ?string $statusLead = null,
        public ?string $meetingStatus = null,
        public ?string $clientType = null,
        /** Filter by catalog service UUID (`services.uuid`). */
        public ?string $serviceUuid = null,
        public ?string $read = null,
        public ?string $spam = null,
        public ?string $scheduledFrom = null,
        public ?string $scheduledTo = null,
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
            'status_lead' => ['nullable', 'string', 'in:New,Called,Pending,Declined'],
            'meeting_status' => ['nullable', 'string', 'in:Confirmed,Rescheduled,Cancelled'],
            'client_type' => ['nullable', 'string', 'in:company,individual'],
            'service_uuid' => ['nullable', 'uuid', 'exists:services,uuid'],
            'read' => ['nullable', 'string', 'in:read,unread'],
            'spam' => ['nullable', 'string', 'in:spam,ham'],
            'scheduled_from' => ['nullable', 'date'],
            'scheduled_to' => ['nullable', 'date', 'after_or_equal:scheduled_from'],
        ];
    }
}
