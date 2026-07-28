<?php

declare(strict_types=1);

namespace Modules\Products\Infrastructure\Persistence\Eloquent\Models;

use Database\Factories\VideoCourseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\Products\Domain\Enums\VideoPlatform;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * 1:1 detail row for `type = video_tutorial | video_pill` products.
 *
 * @property int $id
 * @property string $uuid
 * @property int $product_id
 * @property VideoPlatform|null $platform
 * @property string|null $playlist_url
 * @property int $total_videos
 * @property int|null $total_duration_minutes
 * @property string|null $target_audience
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ProductEloquentModel $product
 *
 * @mixin \Eloquent
 */
#[Table('video_courses')]
#[Fillable([
    'uuid',
    'product_id',
    'platform',
    'playlist_url',
    'total_videos',
    'total_duration_minutes',
    'target_audience',
])]
final class VideoCourseEloquentModel extends Model
{
    /** @use HasFactory<VideoCourseFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $hidden = ['id'];

    protected static function booted(): void
    {
        self::creating(function (VideoCourseEloquentModel $model): void {
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
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'product_id' => 'integer',
            'platform' => VideoPlatform::class,
            'total_videos' => 'integer',
            'total_duration_minutes' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'platform',
                'playlist_url',
                'total_videos',
                'total_duration_minutes',
                'target_audience',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('video_courses');
    }

    protected static function newFactory(): VideoCourseFactory
    {
        return VideoCourseFactory::new();
    }
}
