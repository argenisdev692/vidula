<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Auth\Application\Commands\LoginWithSocialite\LoginWithSocialiteCommand;
use Modules\Auth\Application\Commands\LoginWithSocialite\LoginWithSocialiteHandler;
use Laravel\Socialite\Facades\Socialite;

final class SocialiteController extends Controller
{
    private const ALLOWED_PROVIDERS = ['google'];

    public function __construct(
        private readonly LoginWithSocialiteHandler $loginHandler,
    ) {
    }

    /**
     * @OA\Post(
     *     path="/api/auth/{provider}/callback",
     *     summary="Login with Social Provider Token",
     *     tags={"Auth"},
     *     @OA\Response(response=200, description="Returns Sanctum access token")
     * )
     */
    public function callback(Request $request, string $provider): JsonResponse
    {
        if (!in_array($provider, self::ALLOWED_PROVIDERS, true)) {
            return response()->json(['message' => "OAuth provider is not supported."], 400);
        }

        $request->validate([
            'provider_token' => ['required', 'string'],
        ]);

        try {
            /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
            $driver = Socialite::driver($provider);
            $socialiteUser = $driver->stateless()->userFromToken($request->post('provider_token'));
        } catch (\Exception $e) {
            Log::error('API Socialite verification failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Authentication with provider failed.',
            ], 401);
        }

        $result = DB::transaction(fn() => $this->loginHandler->handle(
            new LoginWithSocialiteCommand(
                provider: $provider,
                providerId: (string) $socialiteUser->getId(),
                email: $socialiteUser->getEmail(),
                name: $socialiteUser->getName(),
                nickname: $socialiteUser->getNickname(),
                avatar: $socialiteUser->getAvatar(),
                token: $socialiteUser->token,
                refreshToken: $socialiteUser->refreshToken,
                expiresIn: $socialiteUser->expiresIn ? (int) $socialiteUser->expiresIn : null,
                ipAddress: $request->ip() ?? 'unknown',
                userAgent: $request->userAgent() ?? 'unknown',
            ),
        ));

        // Create Sanctum Token
        /** @var \Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserEloquentModel&\Laravel\Sanctum\HasApiTokens $userModel */
        $userModel = \Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserEloquentModel::find($result['user']->id);
        $token = $userModel->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Authentication successful.',
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }
}
