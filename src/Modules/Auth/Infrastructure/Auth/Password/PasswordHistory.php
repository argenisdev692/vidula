<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Auth\Password;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Enforces the "no reuse of the last N passwords" policy (prompt §5).
 * Compares a candidate plaintext password against the user's current password
 * and the last {HISTORY_SIZE} stored hashes.
 */
final readonly class PasswordHistory
{
    private const int HISTORY_SIZE = 5;

    /**
     * @throws ValidationException when the password matches a recent one.
     */
    public function assertNotReused(User $user, string $plainPassword): void
    {
        $hashes = $user->passwordHistories()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::HISTORY_SIZE)
            ->pluck('password_hash')
            ->push($user->password)            // the current password also counts
            ->filter()
            ->all();

        foreach ($hashes as $hash) {
            if (Hash::check($plainPassword, (string) $hash)) {
                throw ValidationException::withMessages([
                    'password' => __('You may not reuse one of your last :count passwords.', ['count' => self::HISTORY_SIZE]),
                ]);
            }
        }
    }

    /** Records a hash and prunes the history to the most recent N entries. */
    public function record(User $user, string $hashedPassword): void
    {
        $user->passwordHistories()->create(['password_hash' => $hashedPassword]);

        $keepIds = $user->passwordHistories()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::HISTORY_SIZE)
            ->pluck('id');

        $user->passwordHistories()->whereNotIn('id', $keepIds)->delete();
    }
}
