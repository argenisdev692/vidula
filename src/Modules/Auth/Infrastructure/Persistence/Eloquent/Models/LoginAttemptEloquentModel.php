<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Persistence\Eloquent\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Append-only audit trail of authentication attempts (success + failure) with
 * IP + user agent. Not soft-deletable by design — it is an immutable log that
 * is pruned by age via MassPrunable (BACKEND-PHP §4.1 #11).
 *
 * No LogsActivity: this model *is* the authentication audit trail; wrapping it
 * in activitylog would duplicate rows and risk logging PII twice.
 *
 * @internal
 */
#[Table('login_attempts')]
#[Fillable(['uuid', 'email', 'ip_address', 'country', 'user_agent', 'successful', 'user_uuid', 'guard'])]
final class LoginAttemptEloquentModel extends Model
{
    use MassPrunable;

    protected static function booted(): void
    {
        self::creating(function (LoginAttemptEloquentModel $attempt): void {
            if (empty($attempt->uuid)) {
                $attempt->uuid = (string) Str::uuid7();
            }
        });
    }

    /** Prune attempts older than 6 months to keep indexes lean. */
    public function prunable(): Builder
    {
        return self::query()->where('created_at', '<=', now()->subMonths(6));
    }

    /**
     * Successful attempts store the owning user's public UUID (not the integer PK).
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_uuid', 'uuid');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'successful' => 'boolean',
        ];
    }
}
