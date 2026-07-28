<?php

declare(strict_types=1);

namespace Modules\Products\Infrastructure\Persistence\Eloquent\Models;

use Database\Factories\ProductSessionTopicFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\Enrollments\Infrastructure\Persistence\Eloquent\Models\ClassroomAttendanceEloquentModel;

/**
 * A topic of a session (`Tema k` / one video). The unit the generation
 * pipeline grounds and writes: one topic → one script (+ optional materials).
 *
 * `sources_json` holds the Tavily / Context7 references used for this topic so
 * a reviewer can trace where the content came from.
 *
 * @property int $id
 * @property string $uuid
 * @property int $product_session_id
 * @property string $title
 * @property string|null $description
 * @property string|null $hours
 * @property int $sort_order
 * @property array<int, array<string, mixed>>|null $sources_json
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ProductSessionEloquentModel $session
 * @property-read ProductScriptEloquentModel|null $script
 *
 * @mixin \Eloquent
 */
#[Table('product_session_topics')]
#[Fillable([
    'uuid',
    'product_session_id',
    'title',
    'description',
    'hours',
    'sort_order',
    'sources_json',
])]
final class ProductSessionTopicEloquentModel extends Model
{
    /** @use HasFactory<ProductSessionTopicFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $hidden = ['id'];

    protected static function booted(): void
    {
        self::creating(function (ProductSessionTopicEloquentModel $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid7();
            }
        });
    }

    /**
     * @return BelongsTo<ProductSessionEloquentModel, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(ProductSessionEloquentModel::class, 'product_session_id');
    }

    /**
     * @return HasOne<ProductScriptEloquentModel, $this>
     */
    public function script(): HasOne
    {
        return $this->hasOne(ProductScriptEloquentModel::class, 'product_session_topic_id');
    }

    /**
     * @return HasMany<ProductMaterialEloquentModel, $this>
     */
    public function materials(): HasMany
    {
        return $this->hasMany(ProductMaterialEloquentModel::class, 'product_session_topic_id');
    }

    /**
     * Optional attendance rows that were marked at topic grain.
     *
     * @return HasMany<ClassroomAttendanceEloquentModel, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(
            ClassroomAttendanceEloquentModel::class,
            'product_session_topic_id',
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'product_session_id' => 'integer',
            'hours' => 'decimal:2',
            'sort_order' => 'integer',
            'sources_json' => 'array',
        ];
    }

    protected static function newFactory(): ProductSessionTopicFactory
    {
        return ProductSessionTopicFactory::new();
    }
}
