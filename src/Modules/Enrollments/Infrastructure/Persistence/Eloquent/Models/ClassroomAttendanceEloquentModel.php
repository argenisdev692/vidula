<?php

declare(strict_types=1);

namespace Modules\Enrollments\Infrastructure\Persistence\Eloquent\Models;

use Database\Factories\ClassroomAttendanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\Enrollments\Domain\Enums\AttendanceStatus;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductSessionEloquentModel;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ProductSessionTopicEloquentModel;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Attendance mark for one enrollment on one product session.
 *
 * @property int $id
 * @property string $uuid
 * @property int $enrollment_id
 * @property int $product_session_id
 * @property int|null $product_session_topic_id
 * @property Carbon $date
 * @property AttendanceStatus $attendance_status
 * @property string|null $observation
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ClassroomEnrollmentEloquentModel $enrollment
 * @property-read ProductSessionEloquentModel $session
 * @property-read ProductSessionTopicEloquentModel|null $topic
 *
 * @mixin \Eloquent
 */
#[Table('classroom_attendances')]
#[Fillable([
    'uuid',
    'enrollment_id',
    'product_session_id',
    'product_session_topic_id',
    'date',
    'attendance_status',
    'observation',
])]
final class ClassroomAttendanceEloquentModel extends Model
{
    /** @use HasFactory<ClassroomAttendanceFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $hidden = ['id'];

    protected static function booted(): void
    {
        self::creating(function (ClassroomAttendanceEloquentModel $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid7();
            }
        });
    }

    /**
     * @return BelongsTo<ClassroomEnrollmentEloquentModel, $this>
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(ClassroomEnrollmentEloquentModel::class, 'enrollment_id');
    }

    /**
     * @return BelongsTo<ProductSessionEloquentModel, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(ProductSessionEloquentModel::class, 'product_session_id');
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
            'enrollment_id' => 'integer',
            'product_session_id' => 'integer',
            'product_session_topic_id' => 'integer',
            'date' => 'date',
            'attendance_status' => AttendanceStatus::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'enrollment_id',
                'product_session_id',
                'date',
                'attendance_status',
                'observation',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('attendances');
    }

    protected static function newFactory(): ClassroomAttendanceFactory
    {
        return ClassroomAttendanceFactory::new();
    }
}
