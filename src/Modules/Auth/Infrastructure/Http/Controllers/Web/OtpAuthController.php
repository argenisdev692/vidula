<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Controllers\Web;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Application\Commands\RecordLoginAttemptHandler;
use Modules\Auth\Domain\Ports\AccountLockoutPort;

/**
 * Passwordless (OTP) login over the web session
 * (spatie/laravel-one-time-passwords). Request emails a 6-digit code; login
 * consumes it and starts a session. Responses are generic to avoid user
 * enumeration. Per-user consume attempts are rate-limited by the package
 * (config one-time-passwords.rate_limit_attempts).
 *
 * Failed code entries feed the SAME persistent account-lockout counter as the
 * password path (10 strikes -> locked 15 min + warning email), so a 6-digit
 * code cannot be brute-forced around the lock. A valid code logs the user in,
 * which fires the Login event and clears the counter.
 */
final readonly class OtpAuthController
{
    public function __construct(
        private RecordLoginAttemptHandler $attempts,
        private AccountLockoutPort $lockout,
    ) {}

    public function request(Request $request): RedirectResponse
    {
        $validated = $request->validate(['email' => ['required', 'email']]);

        User::query()
            ->where('email', Str::lower($validated['email']))
            ->first()
            ?->sendOneTimePassword();

        return back()->with('status', 'otp-sent');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'one_time_password' => ['required', 'string'],
        ]);

        $email = Str::lower($validated['email']);

        // A locked account cannot consume an OTP either — same lock as the
        // password path (closes the 6-digit-code brute-force vector).
        if ($this->lockout->isLocked($email)) {
            throw ValidationException::withMessages([
                'one_time_password' => __('Your account is temporarily locked due to too many failed attempts. Try again later.'),
            ]);
        }

        $user = User::query()->where('email', $email)->first();

        $result = $user?->attemptLoginUsingOneTimePassword($validated['one_time_password']);

        if ($result === null || ! $result->isOk()) {
            // Wrong/expired code counts toward the unified lockout counter and
            // triggers the warning email once the threshold (10) is crossed.
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

        // Receiving the emailed code proves control of the address.
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        $request->session()->regenerate();

        return redirect()->intended('/dashboard');
    }
}
