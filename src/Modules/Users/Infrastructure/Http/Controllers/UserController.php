<?php

declare(strict_types=1);

namespace Modules\Users\Infrastructure\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Authorization\Domain\Ports\PermissionRepositoryPort;
use Modules\Authorization\Domain\Ports\RoleRepositoryPort;
use Modules\Users\Application\Commands\BulkDeleteUsersHandler;
use Modules\Users\Application\Commands\BulkRestoreUsersHandler;
use Modules\Users\Application\Commands\DeleteUserHandler;
use Modules\Users\Application\Commands\InviteUserHandler;
use Modules\Users\Application\Commands\ResendInvitationHandler;
use Modules\Users\Application\Commands\RestoreUserHandler;
use Modules\Users\Application\Commands\SyncUserAccessHandler;
use Modules\Users\Application\Commands\UpdateUserHandler;
use Modules\Users\Application\DTOs\InviteUserData;
use Modules\Users\Application\DTOs\UpdateUserData;
use Modules\Users\Application\DTOs\UserAccessData;
use Modules\Users\Application\DTOs\UserFilterData;
use Modules\Users\Application\Queries\GetUserHandler;
use Modules\Users\Application\Queries\ListUsersHandler;
use Modules\Users\Domain\Exceptions\PrivilegeEscalationException;
use Shared\Application\DTOs\BulkUuidsData;

/**
 * Admin user management. Every route is authorized via `permission:*_USERS`
 * middleware (not roles) — see Routes. Controller stays thin: validate → handler
 * → response.
 */
final readonly class UserController
{
    public function index(Request $request, ListUsersHandler $list, RoleRepositoryPort $roles): InertiaResponse|JsonResponse
    {
        $filters = UserFilterData::validateAndCreate($request);
        $users = $list->handle($filters, (int) $request->integer('per_page', 15));

        if ($request->expectsJson()) {
            return response()->json($users);
        }

        /** @var User $actor */
        $actor = $request->user();

        return Inertia::render('users/Index', [
            'users' => $users,
            'filters' => $filters,
            'availableRoles' => $roles->allAssignableNames(),
            'assignableRoles' => $this->assignableRoleNames($actor, $roles),
        ]);
    }

    public function show(
        string $uuid,
        Request $request,
        GetUserHandler $get,
        RoleRepositoryPort $roles,
        PermissionRepositoryPort $permissions,
    ): InertiaResponse|JsonResponse {
        $user = $get->handle($uuid);

        if ($request->expectsJson()) {
            return response()->json(['data' => $user]);
        }

        /** @var User $actor */
        $actor = $request->user();
        $availableRoles = $roles->allAssignableNames();
        $availablePermissions = $permissions->allActiveNames();

        return Inertia::render('users/Show', [
            'user' => $user,
            'userRoles' => $user->getRoleNames()->all(),
            'directPermissions' => $user->getDirectPermissions()->pluck('name')->all(),
            'effectivePermissions' => $user->getAllPermissions()->pluck('name')->all(),
            'availableRoles' => $availableRoles,
            'availablePermissions' => $availablePermissions,
            'assignableRoles' => $this->assignableRoleNames($actor, $roles),
            'assignablePermissions' => $this->assignablePermissionNames($actor, $availablePermissions),
        ]);
    }

    public function store(Request $request, InviteUserData $data, InviteUserHandler $invite): RedirectResponse
    {
        // Seeding roles at invite time is access management, so it needs the
        // dedicated permission — not just CREATE_USERS.
        abort_unless(
            $data->roles === [] || $request->user()?->can('ASSIGN_ROLES_USERS'),
            403,
            __('You are not allowed to assign roles.'),
        );

        try {
            $uuid = $invite->handle($data, $request->user());
        } catch (PrivilegeEscalationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('Invitation sent.'))->with('uuid', $uuid);
    }

    public function access(string $uuid, Request $request, UserAccessData $data, GetUserHandler $get, SyncUserAccessHandler $sync): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        try {
            $sync->handle($actor, $get->handle($uuid), $data);
        } catch (PrivilegeEscalationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('User access updated.'));
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

    /**
     * Roles the actor may delegate — the whole catalogue for a SUPER_ADMIN,
     * otherwise only the roles they hold (mirrors {@see AssignableAccess}). Drives
     * which options the UI renders enabled; the handler stays authoritative.
     *
     * @return list<string>
     */
    private function assignableRoleNames(User $actor, RoleRepositoryPort $roles): array
    {
        if ($actor->hasRole('SUPER_ADMIN')) {
            return $roles->allAssignableNames();
        }

        return array_values($actor->getRoleNames()->all());
    }

    /**
     * Direct permissions the actor may grant — everything for a SUPER_ADMIN,
     * otherwise the intersection of the active catalogue with what the actor holds.
     *
     * @param  list<string>  $catalogue
     * @return list<string>
     */
    private function assignablePermissionNames(User $actor, array $catalogue): array
    {
        if ($actor->hasRole('SUPER_ADMIN')) {
            return $catalogue;
        }

        $held = $actor->getAllPermissions()->pluck('name')->all();

        return array_values(array_intersect($catalogue, $held));
    }
}
