<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Controllers\Web;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Auth\Infrastructure\Session\SessionRegistry;
use Shared\Domain\Ports\AuditPort;

/**
 * Active browser-session management (prompt §6) plus the client-driven idle
 * logout. Requires SESSION_DRIVER=database (project default).
 */
final readonly class SessionController
{
    /**
     * Auth pages an intended-URL redirect must never point back at (avoids loops
     * and open-redirect abuse once combined with the local-path guard).
     */
    private const array DENIED_INTENDED_PREFIXES = [
        '/login', '/register', '/forgot-password', '/reset-password', '/logout', '/auth', '/two-factor',
    ];

    public function __construct(
        private SessionRegistry $sessions,
        private AuditPort $audit,
    ) {}

    public function index(Request $request): InertiaResponse|JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $list = $this->sessions->listForUser($user, $request->session()->getId());

        return match ($request->expectsJson()) {
            true => response()->json(['data' => $list]),
            false => Inertia::render('Auth/Sessions', ['sessions' => $list]),
        };
    }

    public function destroy(Request $request, string $session): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->sessions->revoke($user, $session);

        $this->audit->log(event: 'auth.session_revoked', subject: $user, causer: $user, logName: 'auth');

        return back()->with('success', 'Session signed out.');
    }

    public function destroyOthers(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->sessions->revokeOthers($user, $request->session()->getId());

        $this->audit->log(event: 'auth.other_sessions_revoked', subject: $user, causer: $user, logName: 'auth');

        return back()->with('success', 'Signed out of all other devices.');
    }

    /**
     * Sign out of every device, including the current one, then drop the caller
     * back on the sign-in screen.
     */
    public function destroyAll(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->sessions->revokeAll($user);
        $this->audit->log(event: 'auth.all_sessions_revoked', subject: $user, causer: $user, logName: 'auth');

        $this->logoutCurrent($request);

        return redirect()->route('login')->with('status', 'You have been signed out on all devices.');
    }

    /**
     * Client-driven logout fired when the idle-warning countdown elapses. Stores a
     * validated, same-origin intended path so the next successful sign-in returns
     * the user to where they were, and flags the login screen to show the
     * "session expired" banner.
     */
    public function idleLogout(Request $request): RedirectResponse
    {
        $intended = $this->safeIntended($request->string('intended')->toString());

        $this->audit->log(
            event: 'auth.idle_logout',
            causer: $request->user(),
            logName: 'auth',
        );

        $this->logoutCurrent($request);

        if ($intended !== null) {
            $request->session()->put('url.intended', $intended);
        }

        return redirect()->route('login')->with('expired', true);
    }

    private function logoutCurrent(Request $request): void
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    /**
     * Accept only local, absolute, same-origin paths that are not auth screens.
     * Rejects protocol-relative (`//host`), scheme-qualified and backslash inputs
     * (OWASP A01/A10 — open redirect).
     */
    private function safeIntended(?string $intended): ?string
    {
        if ($intended === null || $intended === '' || ! str_starts_with($intended, '/')) {
            return null;
        }

        if (str_starts_with($intended, '//') || str_contains($intended, '\\')) {
            return null;
        }

        // Local path only — strip query/fragment without parse_url() (PHP 8.5).
        $path = $intended
            |> (fn (string $s): string => explode('#', $s, 2)[0])
            |> (fn (string $s): string => explode('?', $s, 2)[0]);

        foreach (self::DENIED_INTENDED_PREFIXES as $denied) {
            if (str_starts_with($path, $denied)) {
                return null;
            }
        }

        return $intended;
    }
}
