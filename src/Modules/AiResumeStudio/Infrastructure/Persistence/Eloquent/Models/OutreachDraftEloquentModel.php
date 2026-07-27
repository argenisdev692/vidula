<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models;

use App\Models\User;
use Database\Factories\OutreachDraftFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\AiResumeStudio\Domain\Enums\OutreachKind;
use Modules\AiResumeStudio\Domain\Enums\OutreachStatus;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Table('outreach_drafts')]
#[Fillable([
    'uuid',
    'user_id',
    'job_match_id',
    'studio_run_id',
    'kind',
    'subject',
    'body',
    'language',
    'status',
    'provider',
])]
final class OutreachDraftEloquentModel extends Model
{
    /** @use HasFactory<OutreachDraftFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected static function booted(): void
    {
        self::creating(function (OutreachDraftEloquentModel $model): void {
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
     * @return BelongsTo<JobMatchEloquentModel, $this>
     */
    public function jobMatch(): BelongsTo
    {
        return $this->belongsTo(JobMatchEloquentModel::class, 'job_match_id');
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
            'job_match_id' => 'integer',
            'studio_run_id' => 'integer',
            'kind' => OutreachKind::class,
            'status' => OutreachStatus::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['kind', 'subject', 'status', 'language'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('resume_studio');
    }

    protected static function newFactory(): OutreachDraftFactory
    {
        return OutreachDraftFactory::new();
    }
}
