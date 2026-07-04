<?php

declare(strict_types=1);

namespace Modules\Users\Infrastructure\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Users\Application\Commands\BulkDeleteUsersHandler;
use Modules\Users\Application\Commands\BulkRestoreUsersHandler;
use Modules\Users\Application\Commands\DeleteUserHandler;
use Modules\Users\Application\Commands\InviteUserHandler;
use Modules\Users\Application\Commands\ResendInvitationHandler;
use Modules\Users\Application\Commands\RestoreUserHandler;
use Modules\Users\Application\Commands\UpdateUserHandler;
use Modules\Users\Application\DTOs\InviteUserData;
use Modules\Users\Application\DTOs\UpdateUserData;
use Modules\Users\Application\DTOs\UserFilterData;
use Modules\Users\Application\Queries\GetUserHandler;
use Modules\Users\Application\Queries\ListUsersHandler;
use Shared\Application\DTOs\BulkUuidsData;

/**
 * Admin user management. Every route is authorized via `permission:*_USERS`
 * middleware (not roles) — see Routes. Controller stays thin: validate → handler
 * → response.
 */
final readonly class UserController
{
    public function index(Request $request, ListUsersHandler $list): InertiaResponse|JsonResponse
    {
        $filters = UserFilterData::validateAndCreate($request);
        $users = $list->handle($filters, (int) $request->integer('per_page', 15));

        return $request->expectsJson()
            ? response()->json($users)
            : Inertia::render('users/Index', ['users' => $users, 'filters' => $filters]);
    }

    public function show(string $uuid, GetUserHandler $get): InertiaResponse|JsonResponse
    {
        $user = $get->handle($uuid);

        return request()->expectsJson()
            ? response()->json(['data' => $user])
            : Inertia::render('users/Show', ['user' => $user]);
    }

    public function store(Request $request, InviteUserData $data, InviteUserHandler $invite): RedirectResponse
    {
        $uuid = $invite->handle($data, $request->user()?->uuid);

        return back()->with('success', __('Invitation sent.'))->with('uuid', $uuid);
    }

    public function update(string $uuid, UpdateUserData $data, GetUserHandler $get, UpdateUserHandler $update): RedirectResponse
    {
        $update->handle($get->handle($uuid), $data);

        return back()->with('success', __('User updated.'));
    }

    public function destroy(string $uuid, DeleteUserHandler $delete): RedirectResponse
    {
        $delete->handle($uuid);

        return back()->with('success', __('User suspended.'));
    }

    public function restore(string $uuid, RestoreUserHandler $restore): RedirectResponse
    {
        $restore->handle($uuid);

        return back()->with('success', __('User restored.'));
    }

    public function resendInvitation(string $uuid, GetUserHandler $get, ResendInvitationHandler $resend): RedirectResponse
    {
        $user = $get->handle($uuid);
        abort_unless($user->isPending(), 422, __('User is already active.'));

        $resend->handle($user);

        return back()->with('success', __('Invitation re-sent.'));
    }

    public function bulkDelete(BulkUuidsData $data, BulkDeleteUsersHandler $handler): RedirectResponse
    {
        $count = $handler->handle($data);

        return back()->with('success', __(':count users suspended.', ['count' => $count]));
    }

    public function bulkRestore(BulkUuidsData $data, BulkRestoreUsersHandler $handler): RedirectResponse
    {
        $count = $handler->handle($data);

        return back()->with('success', __(':count users restored.', ['count' => $count]));
    }
}
