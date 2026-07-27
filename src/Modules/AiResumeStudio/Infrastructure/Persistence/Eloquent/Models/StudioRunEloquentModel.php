<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\StudioRunFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\AiResumeStudio\Application\DTOs\StudioFilterData;
use Modules\AiResumeStudio\Domain\Enums\StudioMode;
use Modules\AiResumeStudio\Domain\Enums\StudioRunStatus;
use Modules\AiResumeStudio\Domain\Enums\StudioRunStep;
use Modules\Cvs\Infrastructure\Persistence\Eloquent\Models\CvEloquentModel;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property int $cv_id
 * @property int|null $job_search_config_id
 * @property StudioMode $mode
 * @property StudioRunStep $step
 * @property StudioRunStatus $status
 * @property string|null $error_summary
 * @property array<string, mixed>|null $meta
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @mixin \Eloquent
 */
#[Table('studio_runs')]
#[Fillable([
    'uuid',
    'user_id',
    'cv_id',
    'job_search_config_id',
    'mode',
    'step',
    'status',
    'error_summary',
    'meta',
    'started_at',
    'finished_at',
])]
final class StudioRunEloquentModel extends Model
{
    /** @use HasFactory<StudioRunFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * @param  Builder<StudioRunEloquentModel>  $query
     * @return Builder<StudioRunEloquentModel>
     */
    public function scopeApplyFilters(Builder $query, StudioFilterData $filters): Builder
    {
        return $query
            ->when($filters->search !== null, fn ($q) => $q->where(function ($w) use ($filters): void {
                $term = "%{$filters->search}%";
                $w->where('mode', 'like', $term)
                    ->orWhere('status', 'like', $term);
            }))
            ->when($filters->mode !== null, fn ($q) => $q->where('mode', $filters->mode))
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
            );
    }

    protected static function booted(): void
    {
        self::creating(function (StudioRunEloquentModel $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid7();
            }
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<CvEloquentModel, $this>
     */
    public function cv(): BelongsTo
    {
        return $this->belongsTo(CvEloquentModel::class, 'cv_id');
    }

    /**
     * @return BelongsTo<JobSearchConfigEloquentModel, $this>
     */
    public function jobSearchConfig(): BelongsTo
    {
        return $this->belongsTo(JobSearchConfigEloquentModel::class, 'job_search_config_id');
    }

    /**
     * @return HasMany<RefinedCvEloquentModel, $this>
     */
    public function refinedCvs(): HasMany
    {
        return $this->hasMany(RefinedCvEloquentModel::class, 'studio_run_id');
    }

    /**
     * @return HasMany<JobMatchEloquentModel, $this>
     */
    public function jobMatches(): HasMany
    {
        return $this->hasMany(JobMatchEloquentModel::class, 'studio_run_id');
    }

    /**
     * @return HasMany<OutreachDraftEloquentModel, $this>
     */
    public function outreachDrafts(): HasMany
    {
        return $this->hasMany(OutreachDraftEloquentModel::class, 'studio_run_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'cv_id' => 'integer',
            'job_search_config_id' => 'integer',
            'mode' => StudioMode::class,
            'step' => StudioRunStep::class,
            'status' => StudioRunStatus::class,
            'meta' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['mode', 'step', 'status', 'error_summary'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('resume_studio');
    }

    protected static function newFactory(): StudioRunFactory
    {
        return StudioRunFactory::new();
    }
}
