<?php

declare(strict_types=1);

namespace Modules\Products\Infrastructure\Persistence\Eloquent\Models;

use App\Models\User;
use Database\Factories\ContentGenerationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\Products\Domain\Enums\GenerationStatus;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * One run of the async content-generation pipeline. Operational ledger, so no
 * soft deletes — rows disappear only with their product.
 *
 * `source_markdown` is hidden from serialization (it can reach 1 MB) and is
 * never written to the activity log: the audit trail keeps the stage, the
 * progress and the produced counts, which is what an operator needs to answer
 * "what did this run do", without duplicating the payload.
 *
 * @property int $id
 * @property string $uuid
 * @property int $product_id
 * @property int $user_id
 * @property GenerationStatus $status
 * @property string $mode
 * @property string $source_markdown
 * @property string|null $model
 * @property int $progress
 * @property int $sessions_count
 * @property int $topics_count
 * @property int $scripts_count
 * @property string|null $pdf_path
 * @property string|null $md_path
 * @property string|null $zip_path
 * @property string|null $error
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ProductEloquentModel $product
 * @property-read User $user
 *
 * @mixin \Eloquent
 */
#[Table('content_generations')]
#[Fillable([
    'uuid',
    'product_id',
    'user_id',
    'status',
    'mode',
    'source_markdown',
    'model',
    'progress',
    'sessions_count',
    'topics_count',
    'scripts_count',
    'pdf_path',
    'md_path',
    'zip_path',
    'error',
    'started_at',
    'completed_at',
])]
final class ContentGenerationEloquentModel extends Model
{
    /** @use HasFactory<ContentGenerationFactory> */
    use HasFactory, LogsActivity;

    /**
     * @var list<string>
     */
    protected $hidden = ['id', 'source_markdown'];

    protected static function booted(): void
    {
        self::creating(function (ContentGenerationEloquentModel $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid7();
            }
        });
    }

    /**
     * @return BelongsTo<ProductEloquentModel, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductEloquentModel::class, 'product_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Whether this run still occupies the product's single generation slot.
     */
    public function isInFlight(): bool
    {
        return $this->status->isNonTerminal();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'product_id' => 'integer',
            'user_id' => 'integer',
            'status' => GenerationStatus::class,
            'progress' => 'integer',
            'sessions_count' => 'integer',
            'topics_count' => 'integer',
            'scripts_count' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'status',
                'mode',
                'model',
                'progress',
                'sessions_count',
                'topics_count',
                'scripts_count',
                'started_at',
                'completed_at',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('content_generations');
    }

    protected static function newFactory(): ContentGenerationFactory
    {
        return ContentGenerationFactory::new();
    }
}
