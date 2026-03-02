<?php

declare(strict_types=1);

namespace Modules\Students\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserEloquentModel;

/**
 * StudentEloquentModel
 */
final class StudentEloquentModel extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'students';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Database\Factories\StudentFactory
    {
        return \Database\Factories\StudentFactory::new();
    }

    protected $fillable = [
        'uuid',
        'name',
        'email',
        'phone',
        'dni',
        'birth_date',
        'address',
        'avatar',
        'notes',
        'active',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserEloquentModel::class, 'user_id');
    }

    public function scopeInDateRange(
        Builder $query,
        ?string $from,
        ?string $to,
        string $column = 'created_at'
    ): Builder {
        return $query
            ->when($from, fn($q) => $q->whereDate($column, '>=', $from))
            ->when($to, fn($q) => $q->whereDate($column, '<=', $to));
    }
}
