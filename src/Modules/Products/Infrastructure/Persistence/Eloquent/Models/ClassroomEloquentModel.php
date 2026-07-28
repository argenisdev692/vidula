<?php

declare(strict_types=1);

namespace Modules\Products\Infrastructure\Persistence\Eloquent\Models;

use Database\Factories\ClassroomFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\Enrollments\Infrastructure\Persistence\Eloquent\Models\ClassroomEnrollmentEloquentModel;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * 1:1 detail row for `type = classroom` products (live cohort delivery).
 *
 * @property int $id
 * @property string $uuid
 * @property int $product_id
 * @property int|null $max_students
 * @property string|null $meet_url
 * @property string|null $objectives
 * @property string|null $requirements
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ProductEloquentModel $product
 *
 * @mixin \Eloquent
 */
#[Table('classrooms')]
#[Fillable([
    'uuid',
    'product_id',
    'max_students',
    'meet_url',
    'objectives',
    'requirements',
])]
final class ClassroomEloquentModel extends Model
{
    /** @use HasFactory<ClassroomFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $hidden = ['id'];

    protected static function booted(): void
    {
        self::creating(function (ClassroomEloquentModel $model): void {
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
     * @return HasMany<ClassroomEnrollmentEloquentModel, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(
            ClassroomEnrollmentEloquentModel::class,
            'classroom_id',
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'product_id' => 'integer',
            'max_students' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'max_students',
                'meet_url',
                'objectives',
                'requirements',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('classrooms');
    }

    protected static function newFactory(): ClassroomFactory
    {
        return ClassroomFactory::new();
    }
}
