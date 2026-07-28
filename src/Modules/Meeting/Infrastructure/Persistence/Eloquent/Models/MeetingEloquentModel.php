<?php

declare(strict_types=1);

namespace Modules\Meeting\Infrastructure\Persistence\Eloquent\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\MeetingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\Appointment\Infrastructure\Persistence\Eloquent\Models\AppointmentEloquentModel;
use Modules\Meeting\Application\DTOs\MeetingFilterData;
use Modules\Meeting\Domain\ValueObjects\MeetingStatus;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property string $uuid
 * @property int $organizer_id
 * @property string $title
 * @property string|null $description
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property MeetingStatus $status
 * @property string|null $google_event_id
 * @property string|null $meet_link
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @mixin \Eloquent
 */
#[Table('meetings')]
#[Fillable(['uuid', 'organizer_id', 'title', 'description', 'starts_at', 'ends_at', 'status', 'google_event_id', 'meet_link'])]
final class MeetingEloquentModel extends Model
{
    /** @use HasFactory<MeetingFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $hidden = ['id'];

    protected static function booted(): void
    {
        self::creating(function (MeetingEloquentModel $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid7();
            }
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    /**
     * @return HasMany<MeetingAttendeeEloquentModel, $this>
     */
    public function attendees(): HasMany
    {
        return $this->hasMany(MeetingAttendeeEloquentModel::class, 'meeting_id');
    }

    /**
     * Lead (Appointment) attendees for this meeting — inverse of
     * {@see AppointmentEloquentModel::meetingAttendances()} via the
     * polymorphic `meeting_attendees` pivot (morphMap key `lead`).
     *
     * @return MorphToMany<AppointmentEloquentModel, $this>
     */
    public function appointments(): MorphToMany
    {
        return $this->morphedByMany(
            AppointmentEloquentModel::class,
            'attendable',
            'meeting_attendees',
            'meeting_id',
            'attendable_id',
        );
    }

    /**
     * Reusable list filter (BACKEND-PHP §4.1 — single scope), mirrors
     * `AppointmentEloquentModel::scopeApplyFilters()`. `suspended` status is
     * applied via `onlyTrashed()` at the repository.
     *
     * @param  Builder<MeetingEloquentModel>  $query
     * @return Builder<MeetingEloquentModel>
     */
    public function scopeApplyFilters(Builder $query, MeetingFilterData $filters): Builder
    {
        return $query
            ->when($filters->search !== null, fn ($q) => $q->where(function ($w) use ($filters): void {
                $term = '%'.addcslashes($filters->search, '\\%_').'%';
                $w->where('title', 'like', $term)
                    ->orWhere('description', 'like', $term);
            }))
            ->when($filters->meetingStatus !== null, fn ($q) => $q->where('status', $filters->meetingStatus))
            ->when(
                $filters->dateFrom !== null && $filters->dateTo !== null,
                fn ($q) => $q->whereBetween('created_at', [
                    CarbonImmutable::parse($filters->dateFrom)->startOfDay(),
                    CarbonImmutable::parse($filters->dateTo)->endOfDay(),
                ]),
            )
            ->when(
                $filters->dateFrom !== null && $filters->dateTo === null,
                fn ($q) => $q->where('created_at', '>=', CarbonImmutable::parse($filters->dateFrom)->startOfDay()),
            )
            ->when(
                $filters->dateTo !== null && $filters->dateFrom === null,
                fn ($q) => $q->where('created_at', '<=', CarbonImmutable::parse($filters->dateTo)->endOfDay()),
            )
            ->when(
                $filters->startsFrom !== null && $filters->startsTo !== null,
                fn ($q) => $q->whereBetween('starts_at', [
                    CarbonImmutable::parse($filters->startsFrom)->startOfDay(),
                    CarbonImmutable::parse($filters->startsTo)->endOfDay(),
                ]),
            )
            ->when(
                $filters->startsFrom !== null && $filters->startsTo === null,
                fn ($q) => $q->where('starts_at', '>=', CarbonImmutable::parse($filters->startsFrom)->startOfDay()),
            )
            ->when(
                $filters->startsTo !== null && $filters->startsFrom === null,
                fn ($q) => $q->where('starts_at', '<=', CarbonImmutable::parse($filters->startsTo)->endOfDay()),
            );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => MeetingStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'starts_at', 'ends_at', 'status'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('meeting');
    }

    protected static function newFactory(): MeetingFactory
    {
        return MeetingFactory::new();
    }
}
