<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\Auth\Application\Commands\RecordLoginAttemptHandler;
use Modules\Auth\Application\DTOs\ApiLoginData;
use Modules\Auth\Application\DTOs\AuthUserData;
use Modules\Auth\Domain\Events\UserLoggedOut;
use Modules\Auth\Domain\Ports\AccountLockoutPort;
use Modules\Auth\Infrastructure\Auth\SanctumTokenService;
use Symfony\Component\HttpFoundation\Response;

use function event;

/**
 * Stateless token-based authentication for mobile / external clients (Sanctum
 * personal access tokens). The web app uses session auth via Fortify and must
 * NOT call these endpoints. Documented by Scramble via return types + middleware
 * detection — no manual annotations.
 */
final readonly class AuthController
{
    public function __construct(
        private SanctumTokenService $tokens,
        private RecordLoginAttemptHandler $attempts,
        private AccountLockoutPort $lockout,
    ) {}

    /**
     * Log in and issue an API token.
     *
     * Verifies credentials, enforces email verification + account lockout, then
     * issues a rotated Sanctum token (admins expire in 1h, users in 24h). Returns
     * 422 on bad credentials, 403 when the email is unverified, and 429 with a
     * `retry_after` when the account is locked.
     */
    public function login(ApiLoginData $data, Request $request): JsonResponse
    {
        $email = Str::lower($data->email);

        if ($this->lockout->isLocked($email)) {
            return $this->lockedResponse($email);
        }

        $user = User::query()->where('email', $email)->first();

        if ($user === null || ! Hash::check($data->password, (string) $user->password)) {
            (void) $this->attempts->recordFailure(
                email: $email,
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
                guard: 'api',
            );

            if ($this->lockout->isLocked($email)) {
                return $this->lockedResponse($email);
            }

            throw ValidationException::withMessages([
                'email' => __('These credentials do not match our records.'),
            ]);
        }

        if (! $user->hasVerifiedEmail()) {
            return response()->json([
                'message' => __('Your email address is not verified.'),
            ], Response::HTTP_FORBIDDEN);
        }

        $token = $this->tokens->issueRotated($user, $data->deviceName);

        $this->attempts->recordSuccess(
            email: $email,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
            userUuid: (string) $user->uuid,
            guard: 'api',
        );

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
            'user' => AuthUserData::fromModel($user),
        ]);
    }

    /**
     * Rotate the current API token.
     *
     * Revokes the caller's current token and issues a fresh one with the same
     * device name and a renewed expiry.
     */
    public function refresh(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $token = $this->tokens->refreshCurrent($user);

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
        ]);
    }

    /**
     * Get the authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'data' => AuthUserData::fromModel($user),
        ]);
    }

    /**
     * Log out (revoke the current token).
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $current = $user->currentAccessToken();

        if ($current instanceof PersonalAccessToken) {
            $current->delete();
        }

        event(new UserLoggedOut(
            userUuid: (string) $user->uuid,
            email: (string) $user->email,
            guard: 'api',
        ));

        return response()->json(['message' => __('Logged out.')]);
    }

    private function lockedResponse(string $email): JsonResponse
    {
        $retryAfter = $this->lockout->availableIn($email);

        return response()->json([
            'message' => __('Account temporarily locked due to too many failed attempts.'),
            'retry_after' => $retryAfter,
        ], Response::HTTP_TOO_MANY_REQUESTS, ['Retry-After' => $retryAfter]);
    }
}
