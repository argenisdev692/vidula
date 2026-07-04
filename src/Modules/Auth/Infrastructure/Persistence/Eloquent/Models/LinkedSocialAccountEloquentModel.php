<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Persistence\Eloquent\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * A linked OAuth identity (Google / GitHub). Provider tokens are encrypted at
 * rest (OWASP A04). The pair [provider, provider_user_id] is unique so one
 * social identity can only map to one local account.
 *
 * @internal
 */
#[Table('linked_social_accounts')]
#[Fillable([
    'uuid', 'user_id', 'provider', 'provider_user_id', 'provider_email',
    'avatar', 'token', 'refresh_token', 'token_expires_at',
])]
final class LinkedSocialAccountEloquentModel extends Model
{
    use SoftDeletes;

    protected static function booted(): void
    {
        self::creating(function (LinkedSocialAccountEloquentModel $account): void {
            if (empty($account->uuid)) {
                $account->uuid = (string) Str::uuid7();
            }
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

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
}
