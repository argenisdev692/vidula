<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\JobMatchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\AiResumeStudio\Application\DTOs\StudioFilterData;
use Modules\AiResumeStudio\Domain\Enums\ApplicationStatus;
use Modules\AiResumeStudio\Domain\Enums\JobMatchSource;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @internal Persistence adapter — Application/Domain must not import this class outside ports.
 */
#[Table('job_matches')]
#[Fillable([
    'uuid',
    'user_id',
    'job_search_config_id',
    'studio_run_id',
    'job_title',
    'company_name',
    'job_url',
    'canonical_url',
    'raw_snippet',
    'raw_md',
    'match_score',
    'match_reasoning',
    'source',
    'application_status',
    'first_seen_at',
    'last_seen_at',
])]
final class JobMatchEloquentModel extends Model
{
    /** @use HasFactory<JobMatchFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * @param  Builder<JobMatchEloquentModel>  $query
     * @return Builder<JobMatchEloquentModel>
     */
    public function scopeApplyFilters(Builder $query, StudioFilterData $filters): Builder
    {
        return $query
            ->when($filters->search !== null, fn ($q) => $q->where(function ($w) use ($filters): void {
                $term = "%{$filters->search}%";
                $w->where('job_title', 'like', $term)
                    ->orWhere('company_name', 'like', $term)
                    ->orWhere('canonical_url', 'like', $term);
            }))
            ->when($filters->runUuid !== null, function ($q) use ($filters): void {
                $q->whereHas('studioRun', fn ($r) => $r->where('uuid', $filters->runUuid));
            })
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
        self::creating(function (JobMatchEloquentModel $model): void {
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
     * @return BelongsTo<JobSearchConfigEloquentModel, $this>
     */
    public function jobSearchConfig(): BelongsTo
    {
        return $this->belongsTo(JobSearchConfigEloquentModel::class, 'job_search_config_id');
    }

    /**
     * @return BelongsTo<StudioRunEloquentModel, $this>
     */
    public function studioRun(): BelongsTo
    {
        return $this->belongsTo(StudioRunEloquentModel::class, 'studio_run_id');
    }

    /**
     * @return HasMany<OutreachDraftEloquentModel, $this>
     */
    public function outreachDrafts(): HasMany
    {
        return $this->hasMany(OutreachDraftEloquentModel::class, 'job_match_id')->chaperone();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'job_search_config_id' => 'integer',
            'studio_run_id' => 'integer',
            'match_score' => 'integer',
            'source' => JobMatchSource::class,
            'application_status' => ApplicationStatus::class,
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['job_title', 'company_name', 'match_score', 'application_status', 'source'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('resume_studio');
    }

    protected static function newFactory(): JobMatchFactory
    {
        return JobMatchFactory::new();
    }
}
