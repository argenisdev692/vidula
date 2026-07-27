<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models;

use App\Models\User;
use Database\Factories\RefinedCvFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\AiResumeStudio\Domain\Enums\StudioMode;
use Modules\Cvs\Infrastructure\Persistence\Eloquent\Models\CvEloquentModel;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Table('refined_cvs')]
#[Fillable([
    'uuid',
    'user_id',
    'cv_id',
    'studio_run_id',
    'mode',
    'target_job_title',
    'resume_language',
    'provider',
    'ats_score',
    'refined_md',
    'feedback',
    'version',
])]
final class RefinedCvEloquentModel extends Model
{
    /** @use HasFactory<RefinedCvFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected static function booted(): void
    {
        self::creating(function (RefinedCvEloquentModel $model): void {
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
     * @return BelongsTo<StudioRunEloquentModel, $this>
     */
    public function studioRun(): BelongsTo
    {
        return $this->belongsTo(StudioRunEloquentModel::class, 'studio_run_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'cv_id' => 'integer',
            'studio_run_id' => 'integer',
            'mode' => StudioMode::class,
            'ats_score' => 'integer',
            'feedback' => 'array',
            'version' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['mode', 'target_job_title', 'resume_language', 'ats_score', 'version', 'provider'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('resume_studio');
    }

    protected static function newFactory(): RefinedCvFactory
    {
        return RefinedCvFactory::new();
    }
}
