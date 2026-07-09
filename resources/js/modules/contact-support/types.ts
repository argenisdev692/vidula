/**
 * Contact-support module — snake_case interfaces mirroring the backend
 * {@link \Modules\ContactSupport\Infrastructure\Persistence\Eloquent\Models\ContactSupportEloquentModel}
 * projection. The list query selects a lean column set (no `message`, no
 * `updated_at`); the detail render adds `message` + `updated_at`.
 *
 * A row is ACTIVE when `deleted_at === null` and SUSPENDED (soft-deleted) when it
 * holds a timestamp — the list is homogeneous per the `status` filter. The
 * `readed` flag drives the inbox read/unread badge and the `read` filter.
 */

import type { PaginatedResponse } from '@/modules/company/types';

export type { PaginatedResponse };

/** A row in the contact-supports DataTable (lean list projection). */
export interface ContactSupport {
    uuid: string;
    first_name: string;
    last_name: string;
    email: string;
    phone: string;
    subject: string;
    sms_consent: boolean;
    readed: boolean;
    created_at: string | null;
    deleted_at: string | null;
}

/** The detail render returns the full record (adds `message` + `updated_at`). */
export type ContactSupportDetail = ContactSupport & {
    message: string;
    updated_at?: string | null;
};

/** Active = non-trashed (default); Suspended = soft-deleted (onlyTrashed). */
export type ContactSupportStatus = 'active' | 'suspended';

/** Inbox read/unread filter — maps to the `readed` column. */
export type ContactSupportRead = 'read' | 'unread';

/** Filters echoed back by the server (mirrors ContactSupportFilterData). */
export interface ContactSupportFilters {
    search: string | null;
    status: ContactSupportStatus | null;
    read: ContactSupportRead | null;
    date_from: string | null;
    date_to: string | null;
}

/** The full reactive query state driving the server-side DataTable. */
export interface ContactSupportQuery extends ContactSupportFilters {
    page: number;
    per_page: number;
}
