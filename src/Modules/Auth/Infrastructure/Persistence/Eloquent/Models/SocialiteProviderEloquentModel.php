<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserEloquentModel;

/**
 * SocialiteProviderEloquentModel — OAuth connection between a User and a third-party provider.
 * 
 * @internal — Infrastructure only. Use SocialiteRepositoryPort.
 */
final class SocialiteProviderEloquentModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'socialite_providers';

    protected $fillable = [
        'user_id',
        'provider',
        'provider_id',
        'provider_email',
        'nickname',
        'avatar',
        'token',
        'refresh_token',
        'token_expires_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $hidden = [
        'token',
        'refresh_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
        ];
    }

    /**
     * The user this social connection belongs to.
     *
     * @return BelongsTo<UserEloquentModel, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(UserEloquentModel::class, 'user_id');
    }
}
