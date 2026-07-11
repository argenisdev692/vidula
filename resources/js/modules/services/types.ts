/**
 * Services module — snake_case interfaces mirroring the backend
 * {@link \Modules\Services\Infrastructure\Persistence\Eloquent\Models\ServiceEloquentModel}
 * projection. The list query selects a lean column set + the
 * `user:id,first_name,last_name` relation; `updated_at` is intentionally absent
 * from list rows (only the Show detail carries it).
 *
 * A row is ACTIVE when `deleted_at === null` and SUSPENDED (soft-deleted) when
 * it holds a timestamp — the list is homogeneous per the `status` filter. This
 * is independent of `is_active`, the catalog-visibility flag that controls
 * whether the service appears in the public landing-page select input.
 */

import type { PaginatedResponse } from '@/modules/company/types';

export type { PaginatedResponse };

/** The lean author projection loaded alongside each row. */
export interface ServiceAuthor {
    id: number;
    first_name: string | null;
    last_name: string | null;
}

/** A row in the services DataTable. */
export interface Service {
    uuid: string;
    name: string;
    slug: string;
    description: string | null;
    is_active: boolean;
    sort_order: number;
    user_id: number;
    user?: ServiceAuthor | null;
    created_at: string | null;
    deleted_at: string | null;
}

/** Active = non-trashed (default); Suspended = soft-deleted (onlyTrashed). */
export type ServiceStatus = 'active' | 'suspended';

/** Filters echoed back by the server (mirrors ServiceFilterData). */
export interface ServiceFilters {
    search: string | null;
    status: ServiceStatus | null;
    date_from: string | null;
    date_to: string | null;
}

/** The full reactive query state driving the server-side DataTable. */
export interface ServiceQuery extends ServiceFilters {
    page: number;
    per_page: number;
}
