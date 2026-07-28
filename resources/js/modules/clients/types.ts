/**
 * Clients module — snake_case interfaces mirroring
 * ClientEloquentModel list/detail projections (+ user relation).
 */

import type { PaginatedResponse } from '@/modules/company/types';

export type { PaginatedResponse };

/** Lean owner projection loaded alongside each row. */
export interface ClientOwner {
    id: number;
    first_name: string | null;
    last_name: string | null;
}

/** Domain lifecycle status (distinct from soft-delete Active/Suspended). */
export type ClientLifecycleStatus = 'DRAFT' | 'ACTIVE' | 'ARCHIVED';

/** Soft-delete list filter. */
export type ClientSoftStatus = 'active' | 'suspended';

/** A row in the clients DataTable / detail page. */
export interface Client {
    uuid: string;
    client_name: string;
    email: string | null;
    status: ClientLifecycleStatus;
    phone: string;
    address: string | null;
    tax_id: string | null;
    nif: string | null;
    website: string | null;
    facebook_link: string | null;
    instagram_link: string | null;
    linkedin_link: string | null;
    twitter_link: string | null;
    notes: string | null;
    user_id: number;
    user?: ClientOwner | null;
    invoices_count?: number;
    products_count?: number;
    created_at: string | null;
    updated_at?: string | null;
    deleted_at: string | null;
}

/** Filters echoed back by the server (mirrors ClientFilterData). */
export interface ClientFilters {
    search: string | null;
    status: ClientSoftStatus | null;
    client_status: ClientLifecycleStatus | null;
    date_from: string | null;
    date_to: string | null;
}

/** Reactive query state driving the server-side DataTable. */
export interface ClientQuery extends ClientFilters {
    page: number;
    per_page: number;
}
