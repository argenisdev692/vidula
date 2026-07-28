<?php

declare(strict_types=1);

namespace Modules\Enrollments\Infrastructure\Persistence\Eloquent\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ClassroomEnrollmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\Enrollments\Application\DTOs\EnrollmentFilterData;
use Modules\Enrollments\Domain\Enums\EnrollmentStatus;
use Modules\Products\Infrastructure\Persistence\Eloquent\Models\ClassroomEloquentModel;
use Modules\Students\Infrastructure\Persistence\Eloquent\Models\StudentEloquentModel;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Pivot joining a student to a classroom cohort.
 *
 * @property int $id
 * @property string $uuid
 * @property int $student_id
 * @property int $classroom_id
 * @property Carbon $enrolled_at
 * @property EnrollmentStatus $enrollment_status
 * @property string|null $final_grade
 * @property string|null $notes
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read StudentEloquentModel $student
 * @property-read ClassroomEloquentModel $classroom
 * @property-read Collection<int, ClassroomAttendanceEloquentModel> $attendances
 *
 * @mixin \Eloquent
 */
#[Table('classroom_enrollments')]
#[Fillable([
    'uuid',
    'student_id',
    'classroom_id',
    'enrolled_at',
    'enrollment_status',
    'final_grade',
    'notes',
])]
final class ClassroomEnrollmentEloquentModel extends Model
{
    /** @use HasFactory<ClassroomEnrollmentFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $hidden = ['id'];

    protected static function booted(): void
    {
        self::creating(function (ClassroomEnrollmentEloquentModel $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid7();
            }
        });
    }

    /**
     * @return BelongsTo<StudentEloquentModel, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentEloquentModel::class, 'student_id');
    }

    /**
     * @return BelongsTo<ClassroomEloquentModel, $this>
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(ClassroomEloquentModel::class, 'classroom_id');
    }

    /**
     * @return HasMany<ClassroomAttendanceEloquentModel, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(ClassroomAttendanceEloquentModel::class, 'enrollment_id');
    }

    /**
     * @param  Builder<ClassroomEnrollmentEloquentModel>  $query
     * @return Builder<ClassroomEnrollmentEloquentModel>
     */
    public function scopeApplyFilters(Builder $query, EnrollmentFilterData $filters): Builder
    {
        return $query
            ->when($filters->search !== null, fn ($q) => $q->where(function ($w) use ($filters): void {
                $term = "%{$filters->search}%";
                $w->whereHas('student', fn ($s) => $s->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('dni', 'like', $term))
                    ->orWhereHas('classroom.product', fn ($p) => $p->where('title', 'like', $term));
            }))
            ->when(
                $filters->enrollmentStatus !== null,
                fn ($q) => $q->where('enrollment_status', $filters->enrollmentStatus),
            )
            ->when(
                $filters->classroomUuid !== null,
                fn ($q) => $q->whereHas('classroom', fn ($c) => $c->where('uuid', $filters->classroomUuid)),
            )
            ->when(
                $filters->studentUuid !== null,
                fn ($q) => $q->whereHas('student', fn ($s) => $s->where('uuid', $filters->studentUuid)),
            )
            ->when(
                $filters->dateFrom !== null && $filters->dateTo !== null,
                fn ($q) => $q->whereBetween('enrolled_at', [
                    CarbonImmutable::parse($filters->dateFrom)->startOfDay(),
                    CarbonImmutable::parse($filters->dateTo)->endOfDay(),
                ]),
            )
            ->when(
                $filters->dateFrom !== null && $filters->dateTo === null,
                fn ($q) => $q->where('enrolled_at', '>=', CarbonImmutable::parse($filters->dateFrom)->startOfDay()),
            )
            ->when(
                $filters->dateTo !== null && $filters->dateFrom === null,
                fn ($q) => $q->where('enrolled_at', '<=', CarbonImmutable::parse($filters->dateTo)->endOfDay()),
            );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'student_id' => 'integer',
            'classroom_id' => 'integer',
            'enrolled_at' => 'date',
            'enrollment_status' => EnrollmentStatus::class,
            'final_grade' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'student_id',
                'classroom_id',
                'enrolled_at',
                'enrollment_status',
                'final_grade',
                'notes',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('enrollments');
    }

    protected static function newFactory(): ClassroomEnrollmentFactory
    {
        return ClassroomEnrollmentFactory::new();
    }
}
