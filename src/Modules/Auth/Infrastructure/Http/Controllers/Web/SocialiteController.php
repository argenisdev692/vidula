<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Modules\Auth\Application\Commands\LoginWithSocialite\LoginWithSocialiteCommand;
use Modules\Auth\Application\Commands\LoginWithSocialite\LoginWithSocialiteHandler;

/**
 * SocialiteController — Infrastructure HTTP adapter for OAuth flows.
 *
 * Maps Socialite SDK responses to domain commands.
 * Keeps all Socialite/Laravel imports in Infrastructure layer.
 */
final class SocialiteController extends Controller
{
    /**
     * @var array<int, string>
     */
    private const ALLOWED_PROVIDERS = ['google'];

    public function __construct(
        private readonly LoginWithSocialiteHandler $loginHandler,
    ) {
    }

    /**
     * GET /auth/{provider}
     */
    public function redirect(string $provider = 'google'): RedirectResponse|\Symfony\Component\HttpFoundation\RedirectResponse
    {
        $this->validateProvider($provider);

        /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
        $driver = Socialite::driver($provider);

        return $driver
            ->scopes(['openid', 'profile', 'email'])
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    /**
     * GET /auth/{provider}/callback
     */
    public function callback(Request $request, string $provider = 'google'): RedirectResponse
    {
        $this->validateProvider($provider);

        try {
            /** @var SocialiteUser $socialiteUser */
            $socialiteUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            Log::error('Socialite callback failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return redirect('/login')
                ->with('error', 'Authentication with ' . ucfirst($provider) . ' failed. Please try again.');
        }

        // ── Map SDK response to domain command ──
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

        // Infrastructure concern: session login
        Auth::loginUsingId($result['user']->id, true);

        Log::info('Socialite login success', [
            'user_id' => $result['user']->id,
            'provider' => $provider,
        ]);

        return redirect()->intended('/dashboard');
    }

    private function validateProvider(string $provider): void
    {
        if (!in_array($provider, self::ALLOWED_PROVIDERS, true)) {
            abort(404, "OAuth provider [{$provider}] is not supported.");
        }
    }
}
