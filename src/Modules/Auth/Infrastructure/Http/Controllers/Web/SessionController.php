<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Controllers\Web;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Auth\Infrastructure\Session\SessionRegistry;
use Shared\Domain\Ports\AuditPort;

/**
 * Active browser-session management (prompt §6). Requires
 * SESSION_DRIVER=database (project default).
 */
final readonly class SessionController
{
    public function __construct(
        private SessionRegistry $sessions,
        private AuditPort $audit,
    ) {}

    public function index(Request $request): Response|JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $list = $this->sessions->listForUser($user, $request->session()->getId());

        return $request->expectsJson()
            ? response()->json(['data' => $list])
            : Inertia::render('Auth/Sessions', ['sessions' => $list]);
    }

    public function destroy(Request $request, string $session): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->sessions->revoke($user, $session);

        $this->audit->log(event: 'auth.session_revoked', subject: $user, causer: $user, logName: 'auth');

        return back()->with('status', 'session-revoked');
    }

    public function destroyOthers(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->sessions->revokeOthers($user, $request->session()->getId());

        $this->audit->log(event: 'auth.other_sessions_revoked', subject: $user, causer: $user, logName: 'auth');

        return back()->with('status', 'other-sessions-revoked');
    }
}
