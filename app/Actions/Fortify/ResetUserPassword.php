<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\ResetsUserPasswords;
use Modules\Auth\Infrastructure\Auth\Password\PasswordHistory;
use Modules\Auth\Infrastructure\Notifications\PasswordChangedNotification;

final readonly class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    public function __construct(private PasswordHistory $history) {}

    /**
     * Validate and reset the user's forgotten password.
     *
     * Applies the same no-reuse policy + change notification as an authenticated
     * update (prompt §5) so a forgotten-password reset cannot recycle a recent
     * password.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function reset(User $user, array $input): void
    {
        Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

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
