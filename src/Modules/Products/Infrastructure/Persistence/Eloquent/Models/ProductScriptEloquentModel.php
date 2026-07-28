<?php

declare(strict_types=1);

namespace Modules\Products\Infrastructure\Persistence\Eloquent\Models;

use Database\Factories\ProductScriptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\Products\Domain\Enums\ScriptStatus;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * The generated (and then human-reviewed) content of one topic. Video products
 * fill intro/body/outro; classroom lessons mostly use body + notes.
 *
 * The activity log deliberately tracks only the review metadata — logging the
 * script bodies would duplicate megabytes of AI output into `activity_log`.
 *
 * @property int $id
 * @property string $uuid
 * @property int $product_session_topic_id
 * @property string|null $intro
 * @property string|null $body
 * @property string|null $outro
 * @property string|null $notes
 * @property ScriptStatus $status
 * @property int|null $estimated_minutes
 * @property string|null $generated_by_model
 * @property array<int, array<string, mixed>>|null $sources_json
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ProductSessionTopicEloquentModel $topic
 *
 * @mixin \Eloquent
 */
#[Table('product_scripts')]
#[Fillable([
    'uuid',
    'product_session_topic_id',
    'intro',
    'body',
    'outro',
    'notes',
    'status',
    'estimated_minutes',
    'generated_by_model',
    'sources_json',
])]
final class ProductScriptEloquentModel extends Model
{
    /** @use HasFactory<ProductScriptFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $hidden = ['id'];

    protected static function booted(): void
    {
        self::creating(function (ProductScriptEloquentModel $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid7();
            }
        });
    }

    /**
     * @return BelongsTo<ProductSessionTopicEloquentModel, $this>
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(ProductSessionTopicEloquentModel::class, 'product_session_topic_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'product_session_topic_id' => 'integer',
            'status' => ScriptStatus::class,
            'estimated_minutes' => 'integer',
            'sources_json' => 'array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'status',
                'estimated_minutes',
                'generated_by_model',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('product_scripts');
    }

    protected static function newFactory(): ProductScriptFactory
    {
        return ProductScriptFactory::new();
    }
}
