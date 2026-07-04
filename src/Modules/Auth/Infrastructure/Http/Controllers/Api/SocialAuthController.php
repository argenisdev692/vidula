<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Modules\Auth\Application\Commands\RecordLoginAttemptHandler;
use Modules\Auth\Application\DTOs\AuthUserData;
use Modules\Auth\Domain\Exceptions\SocialAuthException;
use Modules\Auth\Infrastructure\Auth\SanctumTokenService;
use Modules\Auth\Infrastructure\Auth\Social\SocialAccountResolver;
use Modules\Auth\Infrastructure\Auth\Social\SocialiteUserMapper;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * @group Authentication
 *
 * Token-based social login for mobile/native clients: the client performs the
 * OAuth dance with the provider SDK and posts the resulting access token here
 * to exchange it for a Sanctum API token.
 */
final readonly class SocialAuthController
{
    private const array PROVIDERS = ['google', 'github'];

    public function __construct(
        private SocialiteUserMapper $mapper,
        private SocialAccountResolver $resolver,
        private SanctumTokenService $tokens,
        private RecordLoginAttemptHandler $attempts,
    ) {}

    /**
     * Exchange a provider access token for an API token
     *
     * @unauthenticated
     *
     * @bodyParam access_token string required The OAuth access token from the provider SDK. Example: ya29.a0Af...
     *
     * @response 200 scenario="Success" {"token":"5|ghi...","token_type":"Bearer","expires_at":"2026-06-23T10:00:00+00:00","user":{"uuid":"...","email":"jane@example.com"}}
     * @response 401 scenario="Invalid provider token" {"message":"Invalid provider token."}
     * @response 409 scenario="Email already registered" {"message":"This email is already registered..."}
     */
    public function __invoke(Request $request, string $provider): JsonResponse
    {
        if (! in_array($provider, self::PROVIDERS, true)) {
            return response()->json(['message' => __('Unsupported social provider.')], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validated = $request->validate(['access_token' => ['required', 'string']]);

        try {
            $socialiteUser = Socialite::driver($provider)->stateless()->userFromToken($validated['access_token']);
        } catch (Throwable) {
            return response()->json(['message' => __('Invalid provider token.')], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $user = $this->resolver->resolve($this->mapper->map($provider, $socialiteUser));
        } catch (SocialAuthException $exception) {
            return response()->json(['message' => $exception->getMessage()], Response::HTTP_CONFLICT);
        }

        $token = $this->tokens->issueRotated($user, $provider);

        $this->attempts->recordSuccess(
            email: (string) $user->email,
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
}
