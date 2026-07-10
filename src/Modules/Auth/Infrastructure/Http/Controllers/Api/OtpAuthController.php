<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Auth\Application\Commands\RecordLoginAttemptHandler;
use Modules\Auth\Application\DTOs\AuthUserData;
use Modules\Auth\Infrastructure\Auth\SanctumTokenService;
use Symfony\Component\HttpFoundation\Response;

/**
 * Passwordless (OTP) login for API clients: request a code, then exchange the
 * code for a Sanctum token. Uses consumeOneTimePassword() (no session login).
 *
 * Note: the package's `enforce_same_origin` option assumes a stateful origin;
 * for pure stateless API clients set it to `DoNotEnforceOrigin` in
 * config/one-time-passwords.php.
 */
final readonly class OtpAuthController
{
    public function __construct(
        private SanctumTokenService $tokens,
        private RecordLoginAttemptHandler $attempts,
    ) {}

    /**
     * Request a one-time password by email.
     *
     * Always returns 200 with a neutral message (no account enumeration).
     */
    public function request(Request $request): JsonResponse
    {
        $validated = $request->validate(['email' => ['required', 'email']]);

        User::query()
            ->where('email', Str::lower($validated['email']))
            ->first()
            ?->sendOneTimePassword();

        return response()->json([
            'message' => __('If the email exists, a one-time password has been sent.'),
        ]);
    }

    /**
     * Exchange a one-time password for an API token.
     *
     * Returns 422 when the code is invalid or expired.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'one_time_password' => ['required', 'string'],
        ]);

        $email = Str::lower($validated['email']);
        $user = User::query()->where('email', $email)->first();

        $result = $user?->consumeOneTimePassword($validated['one_time_password']);

        if ($user === null || $result === null || ! $result->isOk()) {
            (void) $this->attempts->recordFailure($email, $request->ip(), $request->userAgent(), 'api');

            return response()->json([
                'message' => $result?->validationMessage() ?? __('The provided one-time password is invalid.'),
                'errors' => ['one_time_password' => [$result?->validationMessage() ?? __('Invalid code.')]],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        $token = $this->tokens->issueRotated($user, 'api');

        $this->attempts->recordSuccess($email, $request->ip(), $request->userAgent(), (string) $user->uuid, 'api');

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
            'user' => AuthUserData::fromModel($user),
        ]);
    }
}
