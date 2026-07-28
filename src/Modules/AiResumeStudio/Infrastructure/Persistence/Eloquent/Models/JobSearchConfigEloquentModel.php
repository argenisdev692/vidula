<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models;

use App\Models\User;
use Database\Factories\JobSearchConfigFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\AiResumeStudio\Domain\Enums\JobSearchConfigStatus;
use Modules\AiResumeStudio\Domain\Enums\StudioMode;
use Modules\Cvs\Infrastructure\Persistence\Eloquent\Models\CvEloquentModel;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @internal Persistence adapter — Application/Domain must not import this class outside ports.
 */
#[Table('job_search_configs')]
#[Fillable([
    'uuid',
    'user_id',
    'cv_id',
    'mode',
    'keywords',
    'location_scope',
    'search_language',
    'resume_language',
    'targeting_prompt',
    'schedule_enabled',
    'deep_extract_enabled',
    'auto_send_enabled',
    'provider',
    'status',
])]
final class JobSearchConfigEloquentModel extends Model
{
    /** @use HasFactory<JobSearchConfigFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected static function booted(): void
    {
        self::creating(function (JobSearchConfigEloquentModel $model): void {
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
     * @return HasMany<StudioRunEloquentModel, $this>
     */
    public function studioRuns(): HasMany
    {
        return $this->hasMany(StudioRunEloquentModel::class, 'job_search_config_id')->chaperone();
    }

    /**
     * @return HasMany<JobMatchEloquentModel, $this>
     */
    public function jobMatches(): HasMany
    {
        return $this->hasMany(JobMatchEloquentModel::class, 'job_search_config_id')->chaperone();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'cv_id' => 'integer',
            'mode' => StudioMode::class,
            'schedule_enabled' => 'boolean',
            'deep_extract_enabled' => 'boolean',
            'auto_send_enabled' => 'boolean',
            'status' => JobSearchConfigStatus::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['mode', 'keywords', 'resume_language', 'schedule_enabled', 'deep_extract_enabled', 'auto_send_enabled', 'status'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('resume_studio');
    }

    protected static function newFactory(): JobSearchConfigFactory
    {
        return JobSearchConfigFactory::new();
    }
}
