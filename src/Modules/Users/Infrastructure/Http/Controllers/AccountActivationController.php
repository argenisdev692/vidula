<?php

declare(strict_types=1);

namespace Modules\Users\Infrastructure\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Users\Application\Commands\ActivateAccountHandler;
use Modules\Users\Application\DTOs\ActivateAccountData;
use Symfony\Component\HttpFoundation\Response;

/**
 * Public, signed activation endpoint. GET renders the set-password form, POST
 * sets the password + verifies email in one step, then auto-logs-in the user
 * and drops them on the dashboard. Both verbs share one signed URL (the
 * signature is method-agnostic); single-use is enforced by the Pending guard.
 */
final readonly class AccountActivationController
{
    public function __construct(private ActivateAccountHandler $activate) {}

    public function __invoke(Request $request, string $uuid): Response
    {
        $user = User::query()->where('uuid', $uuid)->firstOrFail();

        // Single-use: an already-activated account cannot reuse the link.
        abort_unless($user->isPending(), 410, __('This invitation has already been used.'));

        return $request->isMethod('post')
            ? $this->store($request, $user)
            : $this->show($request, $user);
    }

    private function show(Request $request, User $user): InertiaResponse
    {
        return Inertia::render('Auth/Activate', [
            'email' => $user->email,
            'firstName' => $user->first_name,
            'actionUrl' => $request->fullUrl(), // same signed URL to POST back to
        ]);
    }

    private function store(Request $request, User $user): RedirectResponse
    {
        $data = ActivateAccountData::validateAndCreate($request);

        $this->activate->handle($user, $data);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended('/dashboard');
    }
}
