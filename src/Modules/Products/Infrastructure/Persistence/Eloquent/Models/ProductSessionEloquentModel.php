<?php

declare(strict_types=1);

namespace Modules\Products\Infrastructure\Persistence\Eloquent\Models;

use Database\Factories\ProductSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\Enrollments\Infrastructure\Persistence\Eloquent\Models\ClassroomAttendanceEloquentModel;

/**
 * A unit of the content tree: `Sesión N` for classrooms, `BLOQUE N` for video
 * products. Same table for both — only the seed shape that produced it differs.
 *
 * @property int $id
 * @property string $uuid
 * @property int $product_id
 * @property int $session_number
 * @property string $title
 * @property Carbon|null $session_date
 * @property string|null $start_time
 * @property string|null $end_time
 * @property string|null $hours
 * @property string|null $notes
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ProductEloquentModel $product
 *
 * @mixin \Eloquent
 */
#[Table('product_sessions')]
#[Fillable([
    'uuid',
    'product_id',
    'session_number',
    'title',
    'session_date',
    'start_time',
    'end_time',
    'hours',
    'notes',
])]
final class ProductSessionEloquentModel extends Model
{
    /** @use HasFactory<ProductSessionFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $hidden = ['id'];

    protected static function booted(): void
    {
        self::creating(function (ProductSessionEloquentModel $model): void {
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
     * @return HasMany<ProductSessionTopicEloquentModel, $this>
     */
    public function topics(): HasMany
    {
        return $this->hasMany(ProductSessionTopicEloquentModel::class, 'product_session_id');
    }

    /**
     * @return HasMany<ClassroomAttendanceEloquentModel, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(
            ClassroomAttendanceEloquentModel::class,
            'product_session_id',
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'product_id' => 'integer',
            'session_number' => 'integer',
            'session_date' => 'date',
            'hours' => 'decimal:2',
        ];
    }

    protected static function newFactory(): ProductSessionFactory
    {
        return ProductSessionFactory::new();
    }
}
