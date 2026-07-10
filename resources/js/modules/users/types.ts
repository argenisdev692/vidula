/**
 * Users module — snake_case interfaces mirroring the admin user projection
 * returned by Modules\Users (App\Models\User). The list select exposes only lean,
 * non-secret columns (password + remember_token are `$hidden`), so the frontend
 * NEVER receives a password and derives lifecycle state from what it can see.
 *
 * Lifecycle (see helpers/userStatus.ts):
 *   · SUSPENDED — soft-deleted (`deleted_at !== null`); only in the "suspended" view.
 *   · PENDING   — invited but not yet activated (`email_verified_at === null`); the
 *                 invite flow sets the password AND verifies the email in one step,
 *                 so an unverified email is the visible proxy for "no password".
 *   · ACTIVE    — activated (`email_verified_at !== null`).
 *
 * UI authorization is ALWAYS permission-based — every mutating control is gated by
 * its own `*_USERS` permission (never by role).
 */

import type { PaginatedResponse } from '@/modules/company/types';

export type { PaginatedResponse };

/** Server-side status filter — matches UserFilterData `in:pending,active,suspended`. */
export type UserStatus = 'pending' | 'active' | 'suspended';

/** A named role reference as loaded onto a user (`roles:id,name`). */
export interface RoleRef {
    id: number;
    name: string;
}

/** A row in the users DataTable (lean list projection). */
export interface User {
    uuid: string;
    first_name: string;
    last_name: string | null;
    username: string | null;
    email: string;
    phone: string | null;
    address_2: string | null;
    email_verified_at: string | null;
    must_change_password: boolean;
    created_at: string | null;
    deleted_at: string | null;
    /** Assigned roles (eager-loaded on the list projection). */
    roles?: RoleRef[];
}

/** The detail render (GET /users/{uuid}) resolves the full record withTrashed. */
export interface UserDetail extends User {
    invited_at?: string | null;
    updated_at?: string | null;
}

/**
 * Editable projection for the Edit page (GET /users/{uuid}/edit) — the full set
 * of columns the admin form manages (profile photo excluded; that stays a
 * self-service concern). Every optional field is nullable; coordinates are
 * numeric and filled silently by the address autocomplete.
 */
export interface UserEditData {
    uuid: string;
    first_name: string;
    last_name: string | null;
    username: string | null;
    email: string;
    phone: string | null;
    date_of_birth: string | null;
    gender: string | null;
    address: string | null;
    address_2: string | null;
    zip_code: string | null;
    city: string | null;
    state: string | null;
    country: string | null;
    country_code: string | null;
    latitude: number | null;
    longitude: number | null;
    must_change_password: boolean;
}

/**
 * Read-only access facts on the user detail (GET /users/{uuid}). The detail page
 * only DISPLAYS access — the role, the DIRECT grants, and the full EFFECTIVE set
 * (direct + inherited via the role). Assigning them happens on the dedicated
 * Access screen (see UserPermissionsProps).
 */
export interface UserAccessProps {
    userRoles: string[];
    directPermissions: string[];
    effectivePermissions: string[];
}

/** A minimal user identity carried by the dedicated permissions screen. */
export interface UserIdentity {
    uuid: string;
    first_name: string;
    last_name: string | null;
    email: string;
}

/**
 * Dedicated per-user Access screen props (GET /users/{uuid}/permissions): the
 * single role plus direct-permission top-ups.
 *   · userRoles      — the role(s) the user currently holds (single-role model).
 *   · availableRoles — the assignable role catalogue for the role select.
 *   · assignableRoles — the subset the acting admin may delegate.
 *   · directPermissions — grants held DIRECTLY (editable checkboxes).
 *   · rolePermissions   — grants inherited VIA the role (checked + locked).
 *   · availablePermissions — the full active catalogue, grouped by module.
 *   · assignablePermissions — the subset the acting admin may grant/revoke.
 */
export interface UserPermissionsProps {
    user: UserIdentity;
    userRoles: string[];
    availableRoles: string[];
    assignableRoles: string[];
    directPermissions: string[];
    rolePermissions: string[];
    availablePermissions: string[];
    assignablePermissions: string[];
}

/* ── Filters / query state ────────────────────────────────────────────────── */

/** Filters echoed back by the server (mirrors UserFilterData). */
export interface UserFilters {
    search: string | null;
    status: UserStatus | null;
    date_from: string | null;
    date_to: string | null;
}

/** The full reactive query state driving the server-side DataTable. */
export interface UserQuery extends UserFilters {
    page: number;
    per_page: number;
}
