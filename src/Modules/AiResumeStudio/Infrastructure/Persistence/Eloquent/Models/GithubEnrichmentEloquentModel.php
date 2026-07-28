<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Infrastructure\Persistence\Eloquent\Models;

use App\Models\User;
use Database\Factories\GithubEnrichmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Cvs\Infrastructure\Persistence\Eloquent\Models\CvEloquentModel;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @internal Persistence adapter — Application/Domain must not import this class outside ports.
 */
#[Table('github_enrichments')]
#[Fillable([
    'uuid',
    'user_id',
    'cv_id',
    'github_username',
    'selected_repos',
    'extra_prompt',
    'repos_summary',
    'last_synced_at',
])]
final class GithubEnrichmentEloquentModel extends Model
{
    /** @use HasFactory<GithubEnrichmentFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected static function booted(): void
    {
        self::creating(function (GithubEnrichmentEloquentModel $model): void {
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
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'cv_id' => 'integer',
            'selected_repos' => 'array',
            'repos_summary' => 'array',
            'last_synced_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['github_username', 'last_synced_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('resume_studio');
    }

    protected static function newFactory(): GithubEnrichmentFactory
    {
        return GithubEnrichmentFactory::new();
    }
}
