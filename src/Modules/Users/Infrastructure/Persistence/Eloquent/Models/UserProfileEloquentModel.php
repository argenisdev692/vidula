<?php

declare(strict_types=1);

namespace Modules\Users\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * UserProfileEloquentModel
 */
final class UserProfileEloquentModel extends Model
{
    protected $table = 'user_profiles';

    protected $fillable = [
        'user_id',
        'bio',
        'social_links',
        'visibility',
    ];

    protected function casts(): array
    {
        return [
            'social_links' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserEloquentModel::class, 'user_id');
    }
}
