<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

/**
 * Shares cross-cutting data with every Inertia response. UI authorization uses
 * `permissions` (never roles) — see project rules. Lives in Shared because every
 * module's pages depend on the shared auth/permissions/flash payload.
 */
final class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        /** @var User|null $user */
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user === null ? null : [
                    'uuid' => $user->uuid,
                    'firstName' => $user->first_name,
                    'lastName' => $user->last_name,
                    'email' => $user->email,
                    'mustChangePassword' => (bool) $user->must_change_password,
                ],
                'permissions' => $user === null
                    ? []
                    : $user->getAllPermissions()->pluck('name')->all(),
            ],
            'flash' => [
                'success' => fn (): ?string => $request->session()->get('success'),
                'error' => fn (): ?string => $request->session()->get('error'),
                'status' => fn (): ?string => $request->session()->get('status'),
            ],
        ];
    }
}
