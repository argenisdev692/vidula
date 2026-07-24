<?php

declare(strict_types=1);

namespace Modules\Students\Infrastructure\Persistence\Eloquent\Models;

use Carbon\CarbonImmutable;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\Students\Application\DTOs\StudentFilterData;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Global LMS learner profile — not tenant/user-scoped.
 *
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $dni
 * @property string|null $address
 * @property string|null $avatar
 * @property string|null $notes
 * @property string $status
 * @property bool $active
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @mixin \Eloquent
 */
#[Table('students')]
#[Fillable([
    'uuid',
    'name',
    'email',
    'phone',
    'dni',
    'address',
    'avatar',
    'notes',
    'status',
    'active',
])]
final class StudentEloquentModel extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $hidden = ['id'];

    protected static function booted(): void
    {
        self::creating(function (StudentEloquentModel $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid7();
            }
        });
    }

    /**
     * Shared list filter (BACKEND-PHP §5.2). Soft-delete `suspended` is
     * applied at the repository via `onlyTrashed()`.
     *
     * @param  Builder<StudentEloquentModel>  $query
     * @return Builder<StudentEloquentModel>
     */
    public function scopeApplyFilters(Builder $query, StudentFilterData $filters): Builder
    {
        return $query
            ->when($filters->search !== null, fn ($q) => $q->where(function ($w) use ($filters): void {
                $term = "%{$filters->search}%";
                $w->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('dni', 'like', $term);
            }))
            ->when(
                $filters->studentStatus !== null,
                fn ($q) => $q->where('status', $filters->studentStatus),
            )
            ->when(
                $filters->active !== null,
                fn ($q) => $q->where('active', $filters->active),
            )
            ->when(
                $filters->dateFrom !== null && $filters->dateTo !== null,
                fn ($q) => $q->whereBetween('created_at', [
                    CarbonImmutable::parse($filters->dateFrom)->startOfDay(),
                    CarbonImmutable::parse($filters->dateTo)->endOfDay(),
                ]),
            )
            ->when(
                $filters->dateFrom !== null && $filters->dateTo === null,
                fn ($q) => $q->where('created_at', '>=', CarbonImmutable::parse($filters->dateFrom)->startOfDay()),
            )
            ->when(
                $filters->dateTo !== null && $filters->dateFrom === null,
                fn ($q) => $q->where('created_at', '<=', CarbonImmutable::parse($filters->dateTo)->endOfDay()),
            );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'email',
                'phone',
                'dni',
                'address',
                'avatar',
                'notes',
                'status',
                'active',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('students');
    }

    protected static function newFactory(): StudentFactory
    {
        return StudentFactory::new();
    }
}
