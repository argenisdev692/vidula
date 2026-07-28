<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Auth\Social;

use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Shared\Infrastructure\Resilience\CircuitBreaker\CircuitBreakerInterface;

/**
 * Single Infrastructure seam for OAuth provider HTTP calls (Google / GitHub).
 * Controllers never talk to Socialite directly — every remote exchange runs
 * through the shared CircuitBreaker so a provider outage fails closed instead
 * of hanging the auth hot path.
 */
final readonly class SocialiteAuthAdapter
{
    public function __construct(private CircuitBreakerInterface $breaker) {}

    /**
     * Builds the provider authorize redirect (local URL construction — not a
     * remote round-trip). Kept here so all Socialite touchpoints share one
     * adapter.
     */
    public function redirect(string $provider): RedirectResponse
    {
        /** @var RedirectResponse $response */
        $response = Socialite::driver($provider)->redirect();

        return $response;
    }

    /**
     * Exchanges the OAuth authorization code for the provider user (web callback).
     */
    public function userFromCallback(string $provider): SocialiteUser
    {
        return $this->breaker->call(
            $this->serviceKey($provider),
            static fn (): SocialiteUser => Socialite::driver($provider)->user(),
        );
    }

    /**
     * Validates a client-supplied provider access token (mobile / API flow).
     */
    public function userFromToken(string $provider, string $accessToken): SocialiteUser
    {
        return $this->breaker->call(
            $this->serviceKey($provider),
            static fn (): SocialiteUser => Socialite::driver($provider)->stateless()->userFromToken($accessToken),
        );
    }

    private function serviceKey(string $provider): string
    {
        return 'socialite:'.$provider;
    }
}
