<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Persistence\Eloquent\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stores past password hashes so a user cannot reuse the last N passwords
 * (prompt §5). Append-only; only `created_at` is tracked.
 *
 * @internal
 */
#[Table('password_histories')]
#[Fillable(['user_id', 'password_hash'])]
final class PasswordHistoryEloquentModel extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
