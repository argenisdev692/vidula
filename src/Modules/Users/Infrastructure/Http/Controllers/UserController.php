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
use Modules\Users\Application\Commands\SetUserPermissionHandler;
use Modules\Users\Application\Commands\SyncUserRolesHandler;
use Modules\Users\Application\Commands\UpdateUserHandler;
use Modules\Users\Application\DTOs\InviteUserData;
use Modules\Users\Application\DTOs\SetPermissionData;
use Modules\Users\Application\DTOs\UpdateUserData;
use Modules\Users\Application\DTOs\UserFilterData;
use Modules\Users\Application\DTOs\UserRolesData;
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
    public function index(Request $request, ListUsersHandler $list): InertiaResponse|JsonResponse
    {
        $filters = UserFilterData::validateAndCreate($request);
        $users = $list->handle($filters, min(max($request->integer('per_page', 15), 1), 100));

        if ($request->expectsJson()) {
            return response()->json($users);
        }

        return Inertia::render('users/Index', [
            'users' => $users,
            'filters' => $filters,
        ]);
    }

    public function show(
        string $uuid,
        Request $request,
        GetUserHandler $get,
    ): InertiaResponse|JsonResponse {
        $user = $get->handle($uuid);

        if ($request->expectsJson()) {
            return response()->json(['data' => $user]);
        }

        // Read-only detail: the role, the DIRECT grants, and the full EFFECTIVE set
        // (direct + inherited via the role) for display only. Access management
        // (assigning the role and direct permissions) lives on the dedicated Access
        // screen ({@see permissions()}).
        return Inertia::render('users/Show', [
            'user' => $user,
            'userRoles' => $user->getRoleNames()->all(),
            'directPermissions' => $user->getDirectPermissions()->pluck('name')->all(),
            'effectivePermissions' => $user->getAllPermissions()->pluck('name')->all(),
        ]);
    }

    public function create(Request $request, RoleRepositoryPort $roles): InertiaResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        return Inertia::render('users/Create', [
            'availableRoles' => $roles->allAssignableNames(),
            'assignableRoles' => $this->assignableRoleNames($actor, $roles),
        ]);
    }

    public function edit(string $uuid, GetUserHandler $get): InertiaResponse
    {
        // Project only the editable columns — never serialise the whole model
        // (it would leak two-factor secrets and other non-editable fields).
        return Inertia::render('users/Edit', [
            'user' => $get->handle($uuid)->only([
                'uuid',
                'first_name',
                'last_name',
                'username',
                'email',
                'phone',
                'date_of_birth',
                'gender',
                'address',
                'address_2',
                'zip_code',
                'city',
                'state',
                'country',
                'country_code',
                'latitude',
                'longitude',
                'must_change_password',
            ]),
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

        return redirect()->route('users.index')->with('success', __('Invitation sent.'))->with('uuid', $uuid);
    }

    public function roles(string $uuid, Request $request, UserRolesData $data, GetUserHandler $get, SyncUserRolesHandler $sync): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        try {
            $sync->handle($actor, $get->handle($uuid), $data->roles);
        } catch (PrivilegeEscalationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('User roles updated.'));
    }

    /**
     * Dedicated per-user Access screen: the single role (searchable single-select)
     * plus direct-permission top-ups (module-grouped, instant toggle). Read model:
     * the user's single role, the assignable role catalogue, the full permission
     * catalogue, which grants the user holds DIRECTLY, which they inherit VIA the
     * role (checked + locked), and the subset the acting admin may delegate.
     */
    public function permissions(string $uuid, Request $request, GetUserHandler $get, RoleRepositoryPort $roles, PermissionRepositoryPort $permissions): InertiaResponse
    {
        $user = $get->handle($uuid);

        /** @var User $actor */
        $actor = $request->user();
        $catalogue = $permissions->allActiveNames();

        return Inertia::render('users/Permissions', [
            'user' => $user->only(['uuid', 'first_name', 'last_name', 'email']),
            'userRoles' => $user->getRoleNames()->all(),
            'availableRoles' => $roles->allAssignableNames(),
            'assignableRoles' => $this->assignableRoleNames($actor, $roles),
            'directPermissions' => $user->getDirectPermissions()->pluck('name')->all(),
            'rolePermissions' => $user->getPermissionsViaRoles()->pluck('name')->all(),
            'availablePermissions' => $catalogue,
            'assignablePermissions' => $this->assignablePermissionNames($actor, $catalogue),
        ]);
    }

    public function setPermission(string $uuid, Request $request, SetPermissionData $data, GetUserHandler $get, SetUserPermissionHandler $handler): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        try {
            $handler->handle($actor, $get->handle($uuid), $data->permission, $data->granted);
        } catch (PrivilegeEscalationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $data->granted ? __('Permission granted.') : __('Permission revoked.'));
    }

    public function update(string $uuid, UpdateUserData $data, GetUserHandler $get, UpdateUserHandler $update): RedirectResponse
    {
        $update->handle($get->handle($uuid), $data);

        return redirect()->route('users.index')->with('success', __('User updated.'));
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
