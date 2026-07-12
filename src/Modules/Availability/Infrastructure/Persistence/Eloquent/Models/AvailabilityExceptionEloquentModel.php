<?php

declare(strict_types=1);

namespace Modules\Availability\Infrastructure\Persistence\Eloquent\Models;

use Database\Factories\AvailabilityExceptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\Availability\Application\DTOs\AvailabilityExceptionFilterData;
use Modules\Availability\Domain\ValueObjects\ExceptionSource;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property string $uuid
 * @property Carbon $date
 * @property bool $is_available
 * @property string|null $start_time
 * @property string|null $end_time
 * @property string|null $reason
 * @property ExceptionSource $source
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @mixin \Eloquent
 */
#[Table('availability_exceptions')]
#[Fillable(['uuid', 'date', 'is_available', 'start_time', 'end_time', 'reason', 'source'])]
final class AvailabilityExceptionEloquentModel extends Model
{
    /** @use HasFactory<AvailabilityExceptionFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $hidden = ['id'];

    protected static function booted(): void
    {
        self::creating(function (AvailabilityExceptionEloquentModel $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid7();
            }
        });
    }

    /**
     * Reusable list filter (BACKEND-PHP §4.1). `suspended` status is applied via
     * `onlyTrashed()` at the repository; the date window narrows to a period.
     *
     * @param  Builder<AvailabilityExceptionEloquentModel>  $query
     * @return Builder<AvailabilityExceptionEloquentModel>
     */
    public function scopeApplyFilters(Builder $query, AvailabilityExceptionFilterData $filters): Builder
    {
        return $query
            ->when($filters->availability === 'open', fn ($q) => $q->where('is_available', true))
            ->when($filters->availability === 'closed', fn ($q) => $q->where('is_available', false))
            ->when($filters->dateFrom !== null, fn ($q) => $q->whereDate('date', '>=', $filters->dateFrom))
            ->when($filters->dateTo !== null, fn ($q) => $q->whereDate('date', '<=', $filters->dateTo));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_available' => 'boolean',
            'source' => ExceptionSource::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['date', 'is_available', 'start_time', 'end_time', 'reason'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('availability.exception');
    }

    protected static function newFactory(): AvailabilityExceptionFactory
    {
        return AvailabilityExceptionFactory::new();
    }
}
