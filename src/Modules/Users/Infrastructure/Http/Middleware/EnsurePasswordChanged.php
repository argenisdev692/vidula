<?php

declare(strict_types=1);

namespace Modules\Users\Infrastructure\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Routes a user flagged with `must_change_password` (admin-forced) to the
 * password-update screen before they can reach the rest of the app. Reuses the
 * existing Auth `password.expired` page, which posts to Fortify's
 * `PUT /user/password` (requires the current password).
 *
 * The flag is cleared automatically on any password write (see User model).
 */
final class EnsurePasswordChanged
{
    /**
     * Routes that must stay reachable to avoid a redirect loop / lockout.
     *
     * @var array<int, string>
     */
    private const ALLOWLIST = [
        'password.expired',
        'user-password.update', // Fortify PUT /user/password
        'logout',
        'two-factor.setup',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User
            && $user->must_change_password
            && ! $request->routeIs(...self::ALLOWLIST)
        ) {
            return redirect()->route('password.expired');
        }

        return $next($request);
    }
}
