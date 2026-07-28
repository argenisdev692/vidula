<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Controllers\Web;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Domain\Exceptions\SocialAuthException;
use Modules\Auth\Infrastructure\Auth\Social\SocialAccountResolver;
use Modules\Auth\Infrastructure\Auth\Social\SocialiteAuthAdapter;
use Modules\Auth\Infrastructure\Auth\Social\SocialiteUserMapper;
use Shared\Domain\Ports\AuditPort;
use Throwable;

/**
 * Web (session) OAuth flow for Google + GitHub. The redirect/callback complete
 * a session login; the successful Auth::login() fires the Login event that the
 * WebAuthenticationListener already audits + records as a login attempt.
 */
final readonly class SocialAuthController
{
    private const array PROVIDERS = ['google', 'github'];

    public function __construct(
        private SocialiteUserMapper $mapper,
        private SocialAccountResolver $resolver,
        private SocialiteAuthAdapter $socialite,
        private AuditPort $audit,
    ) {}

    public function redirect(string $provider): RedirectResponse
    {
        $this->assertSupported($provider);

        return $this->socialite->redirect($provider);
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        $this->assertSupported($provider);

        try {
            $socialiteUser = $this->socialite->userFromCallback($provider);
        } catch (Throwable) {
            return redirect()->route('login')
                ->withErrors(['social' => __('Could not authenticate with :provider.', ['provider' => $provider])]);
        }

        try {
            $user = $this->resolver->resolve($this->mapper->map($provider, $socialiteUser));
        } catch (SocialAuthException $exception) {
            return redirect()->route('login')->withErrors(['social' => $exception->getMessage()]);
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended('/dashboard');
    }

    public function unlink(Request $request, string $uuid): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->socialAccounts()->where('uuid', $uuid)->delete();

        $this->audit->log(
            event: 'auth.social_unlinked',
            subject: $user,
            properties: ['social_account_uuid' => $uuid],
            causer: $user,
            logName: 'auth',
        );

        return back()->with('status', 'social-account-unlinked');
    }

    private function assertSupported(string $provider): void
    {
        if (! in_array($provider, self::PROVIDERS, true)) {
            throw SocialAuthException::unsupportedProvider($provider);
        }
    }
}
