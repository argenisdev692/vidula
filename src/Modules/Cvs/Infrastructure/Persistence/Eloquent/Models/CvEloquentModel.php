<?php

declare(strict_types=1);

namespace Modules\Cvs\Infrastructure\Persistence\Eloquent\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\CvFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models\GithubEnrichmentEloquentModel;
use Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models\JobSearchConfigEloquentModel;
use Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models\RefinedCvEloquentModel;
use Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models\StudioRunEloquentModel;
use Modules\Cvs\Application\DTOs\CvFilterData;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property string $title
 * @property string $niche
 * @property bool $is_primary
 * @property string $file_path
 * @property string $file_type
 * @property string $original_filename
 * @property string|null $raw_text
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Collection<int, GithubEnrichmentEloquentModel> $githubEnrichments
 * @property-read Collection<int, JobSearchConfigEloquentModel> $jobSearchConfigs
 * @property-read Collection<int, StudioRunEloquentModel> $studioRuns
 * @property-read Collection<int, RefinedCvEloquentModel> $refinedCvs
 *
 * @mixin \Eloquent
 */
#[Table('cvs')]
#[Fillable([
    'uuid',
    'user_id',
    'title',
    'niche',
    'is_primary',
    'file_path',
    'file_type',
    'original_filename',
    'raw_text',
])]
final class CvEloquentModel extends Model
{
    /** @use HasFactory<CvFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $hidden = ['id', 'file_path'];

    protected static function booted(): void
    {
        self::creating(function (CvEloquentModel $model): void {
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
     * @return HasMany<GithubEnrichmentEloquentModel, $this>
     */
    public function githubEnrichments(): HasMany
    {
        return $this->hasMany(GithubEnrichmentEloquentModel::class, 'cv_id');
    }

    /**
     * @return HasMany<JobSearchConfigEloquentModel, $this>
     */
    public function jobSearchConfigs(): HasMany
    {
        return $this->hasMany(JobSearchConfigEloquentModel::class, 'cv_id');
    }

    /**
     * @return HasMany<StudioRunEloquentModel, $this>
     */
    public function studioRuns(): HasMany
    {
        return $this->hasMany(StudioRunEloquentModel::class, 'cv_id');
    }

    /**
     * @return HasMany<RefinedCvEloquentModel, $this>
     */
    public function refinedCvs(): HasMany
    {
        return $this->hasMany(RefinedCvEloquentModel::class, 'cv_id');
    }

    /**
     * Shared list/export filter (BACKEND-PHP §5.2). Soft-delete `suspended` is
     * applied at the repository via `onlyTrashed()`.
     *
     * @param  Builder<CvEloquentModel>  $query
     * @return Builder<CvEloquentModel>
     */
    public function scopeApplyFilters(Builder $query, CvFilterData $filters): Builder
    {
        return $query
            ->when($filters->search !== null, fn ($q) => $q->where(function ($w) use ($filters): void {
                $term = "%{$filters->search}%";
                $w->where('title', 'like', $term)
                    ->orWhere('original_filename', 'like', $term)
                    ->orWhere('niche', 'like', $term);
            }))
            ->when(
                $filters->niche !== null,
                fn ($q) => $q->where('niche', $filters->niche),
            )
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

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'is_primary' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'title',
                'niche',
                'is_primary',
                'file_type',
                'original_filename',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('cvs');
    }

    protected static function newFactory(): CvFactory
    {
        return CvFactory::new();
    }
}
