<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Middleware;

use App\Models\User;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Forces a password change when the current password is older than
 * config('security.password.expiry_months') (default 3 months).
 *
 * Self-excludes the routes needed to actually change the password / sign out
 * so it never causes a redirect loop. Appended globally to the web group.
 */
final class EnsurePasswordNotExpired
{
    /** Routes that must stay reachable while a password is expired. */
    private const array EXCLUDED_ROUTES = [
        'password.expired',
        'user-password.update',   // Fortify PUT /user/password
        'password.confirm',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $months = (int) config('security.password.expiry_months', 3);

        if ($months <= 0 || ! $user instanceof User || $this->isExcluded($request)) {
            return $next($request);
        }

        $changedAt = $user->password_changed_at;

        if ($changedAt === null || CarbonImmutable::parse($changedAt)->addMonths($months)->isFuture()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('Your password has expired. Please set a new password.'),
                'code' => 'password_expired',
            ], Response::HTTP_FORBIDDEN);
        }

        return redirect()->route('password.expired');
    }

    private function isExcluded(Request $request): bool
    {
        $name = $request->route()?->getName();

        return $name !== null && in_array($name, self::EXCLUDED_ROUTES, true);
    }
}
