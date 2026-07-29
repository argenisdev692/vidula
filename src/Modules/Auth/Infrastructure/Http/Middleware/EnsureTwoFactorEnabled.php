<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces mandatory two-factor authentication for privileged roles
 * (prompt §3: "2FA obligatorio para roles admin/superadmin").
 *
 * Appended globally to the web + api groups (like EnsurePasswordNotExpired).
 * Self-excludes setup / Fortify 2FA management / logout routes so admins can
 * enroll without a redirect loop. Guests pass through (no authenticated user).
 */
final class EnsureTwoFactorEnabled
{
    /** @var list<string> */
    private const array ENFORCED_ROLES = ['SUPER_ADMIN', 'ADMIN'];

    /**
     * Routes that must stay reachable while a privileged user sets up 2FA.
     *
     * @var list<string>
     */
    private const array EXCLUDED_ROUTES = [
        'two-factor.setup',
        'two-factor.enable',
        'two-factor.confirm',
        'two-factor.disable',
        'two-factor.qr-code',
        'two-factor.secret-key',
        'two-factor.recovery-codes',
        'two-factor.regenerate-recovery-codes',
        'two-factor.trust-device',
        'two-factor.trusted-devices.revoke',
        'password.confirm',
        'password.confirmation',
        'password.confirm.store',
        'password.expired',
        'user-password.update',
        'logout',
        'session.idle-logout',
        'api.auth.logout',
        'api.auth.two-factor',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! (bool) config('security.two_factor.mandatory', false)) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user instanceof User || $this->isExcluded($request)) {
            return $next($request);
        }

        /** @var list<string> $roles */
        $roles = config('security.two_factor.mandatory_roles', self::ENFORCED_ROLES);

        if ($user->hasAnyRole($roles) && ! $user->hasEnabledTwoFactorAuthentication()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('Two-factor authentication is required for your role.'),
                    'code' => 'two_factor_required',
                ], Response::HTTP_FORBIDDEN);
            }

            return redirect()->route('two-factor.setup')
                ->with('status', 'two-factor-required');
        }

        return $next($request);
    }

    private function isExcluded(Request $request): bool
    {
        $name = $request->route()?->getName();

        return $name !== null && in_array($name, self::EXCLUDED_ROUTES, true);
    }
}
