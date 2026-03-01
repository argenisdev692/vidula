<?php

declare(strict_types=1);

namespace Modules\Users\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * UserActivityEloquentModel
 */
final class UserActivityEloquentModel extends Model
{
    protected $table = 'user_activities';

    protected $fillable = [
        'user_id',
        'action',
        'description',
        'metadata',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserEloquentModel::class, 'user_id');
    }
}
