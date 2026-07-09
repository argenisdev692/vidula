<?php

declare(strict_types=1);

namespace Modules\ActivityLog\Application\DTOs;

use Illuminate\Database\Eloquent\Builder;
use Modules\ActivityLog\Application\Queries\ListActivityLogsHandler;
use Shared\Application\DTOs\Concerns\DateRangeFilterRules;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Shared list/export filter for the (read-only) activity-log trail — consumed by
 * both {@see ListActivityLogsHandler}
 * and the export controller through the single {@see self::applyTo()} (DRY).
 *
 * The trail is immutable: there is no status/soft-delete axis, only a query
 * window. `date_from` / `date_to` filter on `created_at`.
 */
#[MapInputName(SnakeCaseMapper::class)]
final class ActivityLogFilterData extends Data
{
    use DateRangeFilterRules;

    public function __construct(
        public ?string $search = null,
        public ?string $event = null,
        public ?string $logName = null,
        public ?string $causerId = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            ...self::dateRangeRules(),
            'event' => ['nullable', 'string', 'max:255'],
            'log_name' => ['nullable', 'string', 'max:255'],
            'causer_id' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Apply every active filter to an Activity query. Single source of truth so
     * the list and the export never drift.
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @return Builder<covariant \Illuminate\Database\Eloquent\Model>
     */
    public function applyTo(Builder $query): Builder
    {
        return $query
            ->when($this->search !== null && $this->search !== '', function (Builder $q): void {
                $term = '%'.$this->search.'%';
                $q->where(function (Builder $inner) use ($term): void {
                    $inner
                        ->where('description', 'like', $term)
                        ->orWhere('event', 'like', $term)
                        ->orWhere('log_name', 'like', $term)
                        ->orWhere('subject_type', 'like', $term);
                });
            })
            ->when($this->event !== null && $this->event !== '', fn (Builder $q) => $q->where('event', $this->event))
            ->when($this->logName !== null && $this->logName !== '', fn (Builder $q) => $q->where('log_name', $this->logName))
            ->when($this->causerId !== null && $this->causerId !== '', fn (Builder $q) => $q->where('causer_id', $this->causerId))
            ->when($this->dateFrom !== null && $this->dateFrom !== '', fn (Builder $q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== null && $this->dateTo !== '', fn (Builder $q) => $q->whereDate('created_at', '<=', $this->dateTo));
    }
}
