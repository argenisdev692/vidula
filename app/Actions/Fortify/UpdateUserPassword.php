<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;
use Modules\Auth\Infrastructure\Auth\Password\PasswordHistory;
use Modules\Auth\Infrastructure\Notifications\PasswordChangedNotification;

final readonly class UpdateUserPassword implements UpdatesUserPasswords
{
    use PasswordValidationRules;

    public function __construct(private PasswordHistory $history) {}

    /**
     * Validate and update the user's password.
     *
     * Enforces the no-reuse policy (last N passwords), records the new hash in
     * history, and emails a change notification (prompt §5). The
     * password_changed_at stamp + must_change_password reset are handled by the
     * User model's `updating` hook, independent of the flow.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => $this->passwordRules(),
        ], [
            'current_password.current_password' => __('The provided password does not match your current password.'),
        ])->validateWithBag('updatePassword');

        $this->history->assertNotReused($user, $input['password']);

        $user->forceFill([
            'password' => Hash::make($input['password']),
        ])->save();

        $this->history->record($user, $user->password);

        $user->notify(new PasswordChangedNotification(
            ipAddress: request()->ip(),
            changedAt: now()->toDayDateTimeString(),
        ));
    }
}
