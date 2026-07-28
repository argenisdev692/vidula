<?php

declare(strict_types=1);

namespace Modules\Products\Infrastructure\Persistence\Eloquent\Models;

use Database\Factories\ProductMaterialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\Products\Domain\Enums\MaterialType;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A downloadable or linked artefact of a product (course PDF, lesson markdown,
 * external reference). Stored files live on a private disk and are served
 * through an authorized download route, never a public URL.
 *
 * @property int $id
 * @property string $uuid
 * @property int $product_id
 * @property int|null $product_session_topic_id
 * @property string $title
 * @property MaterialType $type
 * @property string|null $storage_disk
 * @property string|null $path
 * @property string|null $original_name
 * @property string|null $mime_type
 * @property int|null $size_bytes
 * @property string|null $url
 * @property string|null $content
 * @property bool $is_downloadable
 * @property int $sort_order
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ProductEloquentModel $product
 * @property-read ProductSessionTopicEloquentModel|null $topic
 *
 * @mixin \Eloquent
 */
#[Table('product_materials')]
#[Fillable([
    'uuid',
    'product_id',
    'product_session_topic_id',
    'title',
    'type',
    'storage_disk',
    'path',
    'original_name',
    'mime_type',
    'size_bytes',
    'url',
    'content',
    'is_downloadable',
    'sort_order',
])]
final class ProductMaterialEloquentModel extends Model
{
    /** @use HasFactory<ProductMaterialFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $hidden = ['id'];

    protected static function booted(): void
    {
        self::creating(function (ProductMaterialEloquentModel $model): void {
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
            'product_id' => 'integer',
            'product_session_topic_id' => 'integer',
            'type' => MaterialType::class,
            'size_bytes' => 'integer',
            'is_downloadable' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'title',
                'type',
                'storage_disk',
                'path',
                'original_name',
                'mime_type',
                'size_bytes',
                'is_downloadable',
                'sort_order',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('product_materials');
    }

    protected static function newFactory(): ProductMaterialFactory
    {
        return ProductMaterialFactory::new();
    }
}
