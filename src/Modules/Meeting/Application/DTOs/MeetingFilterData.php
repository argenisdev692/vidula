<?php

declare(strict_types=1);

namespace Modules\Meeting\Application\DTOs;

use Shared\Application\DTOs\SoftDeleteFilterData;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * List/export filter for meetings. Soft-delete `status` is active|suspended
 * (applied via `onlyTrashed()` at the repository); the domain lifecycle lives
 * on `meeting_status`. Sort follows BACKEND-PHP §5.2 (Products pattern);
 * `page`/`per_page` stay on the request (capped in the controller).
 */
#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
final class MeetingFilterData extends SoftDeleteFilterData
{
    /** @var list<string> */
    public const array SORTABLE = ['starts_at', 'created_at', 'title', 'status'];

    public function __construct(
        ?string $search = null,
        ?string $status = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        public ?string $meetingStatus = null,
        public ?string $startsFrom = null,
        public ?string $startsTo = null,
        public string $sortField = 'starts_at',
        public int $sortOrder = -1,
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
            'sort_field' => ['nullable', 'string', 'in:starts_at,created_at,title,status'],
            'sort_order' => ['nullable', 'integer', 'in:1,-1'],
        ];
    }

    public function resolvedSortField(): string
    {
        return in_array($this->sortField, self::SORTABLE, true)
            ? $this->sortField
            : 'starts_at';
    }

    public function resolvedSortDirection(): string
    {
        return $this->sortOrder === 1 ? 'asc' : 'desc';
    }
}
