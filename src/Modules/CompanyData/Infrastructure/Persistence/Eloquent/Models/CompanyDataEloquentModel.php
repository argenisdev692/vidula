<?php

declare(strict_types=1);

namespace Modules\CompanyData\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserEloquentModel;

/**
 * @internal Infrastructure-only — never import from Domain or Application layers.
 */
final class CompanyDataEloquentModel extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'company_data';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Database\Factories\CompanyDataFactory
    {
        return \Database\Factories\CompanyDataFactory::new();
    }

    protected $fillable = [
        'uuid',
        'user_id',
        'company_name',
        'name',
        'email',
        'phone',
        'address',
        'website',
        'facebook_link',
        'instagram_link',
        'linkedin_link',
        'twitter_link',
        'latitude',
        'longitude',
        'signature_path',
        'status',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'company_name',
                'name',
                'email',
                'phone',
                'address',
                'website',
                'facebook_link',
                'instagram_link',
                'linkedin_link',
                'twitter_link',
                'latitude',
                'longitude',
                'status',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('company.company_data');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserEloquentModel::class, 'user_id');
    }

    public function scopeInDateRange(
        Builder $query,
        ?string $from,
        ?string $to,
        string $column = 'created_at',
    ): Builder {
        return $query
            ->when($from, fn($q) => $q->whereDate($column, '>=', $from))
            ->when($to, fn($q) => $q->whereDate($column, '<=', $to));
    }
}
