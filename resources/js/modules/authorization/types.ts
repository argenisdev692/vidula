/**
 * Authorization module — snake_case interfaces mirroring the backend Role /
 * Permission projections (Modules\Authorization\Infrastructure\Persistence\
 * Eloquent\Models). Both entities are soft-deletable: a row is ACTIVE when
 * `deleted_at === null` and SUSPENDED (soft-deleted) when it holds a timestamp;
 * the list is homogeneous per the `status` filter.
 *
 * UI authorization is ALWAYS permission-based. Every mutating control is gated by
 * its own `*_ROLES` / `*_PERMISSIONS` permission (never by role).
 */

import type { PaginatedResponse } from '@/modules/company/types';

export type { PaginatedResponse };

/** Active = non-trashed (default); Suspended = soft-deleted (onlyTrashed). */
export type AuthorizationStatus = 'active' | 'suspended';

/* ── Permission ───────────────────────────────────────────────────────────── */

/** A permission grant nested on a role (lean `id,name` projection). */
export interface RolePermission {
    id: number;
    name: string;
}

/** A row in the roles DataTable (list projection + counts). */
export interface Role {
    uuid: string;
    name: string;
    guard_name: string;
    permissions_count: number;
    permissions?: RolePermission[];
    created_at: string | null;
    deleted_at: string | null;
}

/** The detail render adds `updated_at` and the full permissions relation. */
export type RoleDetail = Role & { updated_at?: string | null };

/** A role that holds a permission (lean `id,name` projection). */
export interface PermissionRole {
    id: number;
    name: string;
}

/** A row in the permissions DataTable (list projection + role usage). */
export interface Permission {
    uuid: string;
    name: string;
    guard_name: string;
    roles_count: number;
    roles?: PermissionRole[];
    created_at: string | null;
    deleted_at: string | null;
}

/** The detail render adds `updated_at`. */
export type PermissionDetail = Permission & { updated_at?: string | null };

/* ── Filters / query state (shared shape) ─────────────────────────────────── */

/** Filters echoed back by the server (mirrors Role/PermissionFilterData). */
export interface AuthorizationFilters {
    search: string | null;
    status: AuthorizationStatus | null;
    date_from: string | null;
    date_to: string | null;
}

/** The full reactive query state driving a server-side DataTable. */
export interface AuthorizationQuery extends AuthorizationFilters {
    page: number;
    per_page: number;
}
