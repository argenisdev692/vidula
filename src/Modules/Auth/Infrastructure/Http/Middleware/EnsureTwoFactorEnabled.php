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
 * A privileged user without confirmed 2FA is redirected to the setup page on
 * the web, or refused with 403 on JSON/API requests.
 */
final class EnsureTwoFactorEnabled
{
    private const array ENFORCED_ROLES = ['SUPER_ADMIN', 'ADMIN'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User
            && $user->hasAnyRole(self::ENFORCED_ROLES)
            && ! $user->hasEnabledTwoFactorAuthentication()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('Two-factor authentication is required for your role.'),
                ], Response::HTTP_FORBIDDEN);
            }

            return redirect()->route('two-factor.setup')
                ->with('status', 'two-factor-required');
        }

        return $next($request);
    }
}
