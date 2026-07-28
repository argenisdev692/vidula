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
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A device the user chose to trust for 30 days, so 2FA is skipped on it.
 * The raw token lives only in the user's (encrypted) cookie; we persist the
 * sha256 hash — never the token itself.
 *
 * @internal
 */
#[Table('trusted_devices')]
#[Fillable(['uuid', 'user_id', 'token_hash', 'user_agent', 'ip_address', 'last_used_at', 'expires_at'])]
final class TrustedDeviceEloquentModel extends Model
{
    use LogsActivity, SoftDeletes;

    protected static function booted(): void
    {
        self::creating(function (TrustedDeviceEloquentModel $device): void {
            if (empty($device->uuid)) {
                $device->uuid = (string) Str::uuid7();
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
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Allowlist only — NEVER log token_hash (secret-equivalent).
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'uuid',
                'user_id',
                'user_agent',
                'ip_address',
                'last_used_at',
                'expires_at',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('auth.trusted_device');
    }
}
