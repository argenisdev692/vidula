<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Controllers\Web;

use App\Actions\Fortify\ResetUserPassword;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Application\Commands\RecordLoginAttemptHandler;
use Modules\Auth\Domain\Ports\AccountLockoutPort;

/**
 * Passwordless password reset over the web session. Step 1 emails a 6-digit code
 * (spatie one-time-password, valid 30 min); step 2 consumes the code and sets a
 * new password through the shared ResetUserPassword action (no-reuse history +
 * change notification). Responses are generic to avoid user enumeration.
 *
 * Wrong/expired codes feed the SAME persistent account-lockout counter as the
 * login paths, and the package's per-user attempt limit
 * (one-time-passwords.rate_limit_attempts) throttles brute-forcing the 6 digits.
 */
final readonly class PasswordResetController
{
    public function __construct(
        private RecordLoginAttemptHandler $attempts,
        private AccountLockoutPort $lockout,
        private ResetUserPassword $resetPassword,
    ) {}

    public function request(Request $request): RedirectResponse
    {
        $validated = $request->validate(['email' => ['required', 'email']]);

        User::query()
            ->where('email', Str::lower($validated['email']))
            ->first()
            ?->sendOneTimePassword((int) config('security.otp.email_minutes', 30));

        return back()->with('status', 'reset-otp-sent');
    }

    public function reset(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'one_time_password' => ['required', 'string'],
        ]);

        $email = Str::lower($validated['email']);

        if ($this->lockout->isLocked($email)) {
            throw ValidationException::withMessages([
                'one_time_password' => __('Your account is temporarily locked due to too many failed attempts. Try again later.'),
            ]);
        }

        $user = User::query()->where('email', $email)->first();

        $result = $user?->consumeOneTimePassword($validated['one_time_password']);

        if ($user === null || $result === null || ! $result->isOk()) {
            (void) $this->attempts->recordFailure(
                email: $email,
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
                guard: 'web',
            );

            throw ValidationException::withMessages([
                'one_time_password' => $result?->validationMessage() ?? __('The provided code is invalid.'),
            ]);
        }

        // Delegates password validation (rules + confirmation), the no-reuse
        // history check and the change-notification email to the shared action.
        $this->resetPassword->reset($user, $request->only(['password', 'password_confirmation']));

        // A valid emailed code also proves control of the address.
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        $this->lockout->clear($email);

        return redirect('/')->with('status', 'password-reset');
    }
}
