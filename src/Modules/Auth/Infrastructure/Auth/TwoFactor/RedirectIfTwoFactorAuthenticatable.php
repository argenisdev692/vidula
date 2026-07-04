<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Auth\TwoFactor;

use Illuminate\Http\Request;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable as FortifyRedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\TwoFactorAuthenticatable;

use function class_uses_recursive;

/**
 * Drop-in replacement for Fortify's 2FA login pipe that honours a 30-day
 * "trusted device" cookie: a user with 2FA enabled on a trusted device skips
 * the challenge (the password is still verified by validateCredentials()).
 *
 * Mirrors the parent's branching exactly — credentials are validated ONCE —
 * and only inserts the trusted-device short-circuit. Bound via
 * Fortify::redirectUserForTwoFactorAuthenticationUsing() in FortifyServiceProvider.
 */
final class RedirectIfTwoFactorAuthenticatable extends FortifyRedirectIfTwoFactorAuthenticatable
{
    /**
     * @param  Request  $request
     * @param  callable  $next
     * @return mixed
     */
    public function handle($request, $next)
    {
        $user = $this->validateCredentials($request);

        if ($this->userHasTwoFactorEnabled($user)) {
            if (app(TrustedDeviceManager::class)->isTrusted($request, $user)) {
                return $next($request);
            }

            return $this->twoFactorChallengeResponse($request, $user);
        }

        return $next($request);
    }

    private function userHasTwoFactorEnabled(mixed $user): bool
    {
        if ($user === null || ! in_array(TwoFactorAuthenticatable::class, class_uses_recursive($user), true)) {
            return false;
        }

        if (Fortify::confirmsTwoFactorAuthentication()) {
            return (bool) $user->two_factor_secret && $user->two_factor_confirmed_at !== null;
        }

        return (bool) $user->two_factor_secret;
    }
}
