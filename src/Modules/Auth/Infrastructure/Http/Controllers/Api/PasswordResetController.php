<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Controllers\Api;

use App\Actions\Fortify\ResetUserPassword;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Auth\Application\Commands\RecordLoginAttemptHandler;
use Modules\Auth\Domain\Ports\AccountLockoutPort;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group Authentication
 *
 * Passwordless password reset for API clients: request a 6-digit code by email,
 * then submit the code plus a new password. Uses spatie one-time-passwords
 * (codes valid 30 min) — the same mechanism as OTP login and activation.
 */
final readonly class PasswordResetController
{
    public function __construct(
        private RecordLoginAttemptHandler $attempts,
        private AccountLockoutPort $lockout,
        private ResetUserPassword $resetPassword,
    ) {}

    /**
     * Request a password-reset code by email
     *
     * @unauthenticated
     *
     * @bodyParam email string required Example: jane@example.com
     *
     * @response 200 {"message":"If the email exists, a reset code has been sent."}
     */
    public function request(Request $request): JsonResponse
    {
        $validated = $request->validate(['email' => ['required', 'email']]);

        User::query()
            ->where('email', Str::lower($validated['email']))
            ->first()
            ?->sendOneTimePassword((int) config('security.otp.email_minutes', 30));

        return response()->json([
            'message' => __('If the email exists, a reset code has been sent.'),
        ]);
    }

    /**
     * Reset the password with a 6-digit code
     *
     * @unauthenticated
     *
     * @bodyParam email string required Example: jane@example.com
     * @bodyParam one_time_password string required The emailed code. Example: 123456
     * @bodyParam password string required The new password. Example: Sup3rS3cret!2026
     * @bodyParam password_confirmation string required Must match password. Example: Sup3rS3cret!2026
     *
     * @response 200 scenario="Success" {"message":"Your password has been reset."}
     * @response 422 scenario="Invalid code" {"message":"The provided code is invalid.","errors":{"one_time_password":["..."]}}
     * @response 429 scenario="Account locked" {"message":"Account temporarily locked due to too many failed attempts.","retry_after":840}
     */
    public function reset(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'one_time_password' => ['required', 'string'],
        ]);

        $email = Str::lower($validated['email']);

        if ($this->lockout->isLocked($email)) {
            return response()->json([
                'message' => __('Account temporarily locked due to too many failed attempts.'),
                'retry_after' => $this->lockout->availableIn($email),
            ], Response::HTTP_TOO_MANY_REQUESTS, ['Retry-After' => $this->lockout->availableIn($email)]);
        }

        $user = User::query()->where('email', $email)->first();

        $result = $user?->consumeOneTimePassword($validated['one_time_password']);

        if ($user === null || $result === null || ! $result->isOk()) {
            (void) $this->attempts->recordFailure($email, $request->ip(), $request->userAgent(), 'api');

            return response()->json([
                'message' => $result?->validationMessage() ?? __('The provided code is invalid.'),
                'errors' => ['one_time_password' => [$result?->validationMessage() ?? __('Invalid code.')]],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->resetPassword->reset($user, $request->only(['password', 'password_confirmation']));

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        $this->lockout->clear($email);

        return response()->json(['message' => __('Your password has been reset.')]);
    }
}
