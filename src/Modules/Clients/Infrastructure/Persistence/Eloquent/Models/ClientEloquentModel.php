<?php

declare(strict_types=1);

namespace Modules\Clients\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserEloquentModel;

/**
 * ClientEloquentModel
 */
/**
 * @internal — Only the ClientMapper may access this model directly.
 */
final class ClientEloquentModel extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'clients';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Database\Factories\ClientFactory
    {
        return \Database\Factories\ClientFactory::new();
    }

    protected $fillable = [
        'uuid',
        'user_id',
        'company',
        'email',
        'phone',
        'address',
        'tax_id',
        'nif',
        'website',
        'facebook_link',
        'instagram_link',
        'linkedin_link',
        'twitter_link',
        'latitude',
        'longitude',
        'notes',
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
