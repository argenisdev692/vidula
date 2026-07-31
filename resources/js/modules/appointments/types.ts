/**
 * Appointments module — snake_case interfaces mirroring the backend
 * {@link \Modules\Appointment\Infrastructure\Persistence\Eloquent\Models\AppointmentEloquentModel}
 * projection. A lead is captured once (public landing page or admin) and
 * progresses through `status_lead` while a booked meeting progresses through
 * `meeting_status` — the two are independent (mirrors the DB comment on the
 * migration). The list query selects a lean column set (no address, notes,
 * spam or follow-up-call detail); the detail render (Show) returns the full
 * record.
 *
 * A row is ACTIVE when `deleted_at === null` and SUSPENDED (soft-deleted)
 * otherwise — the list is homogeneous per the `status` filter, exactly like
 * Users / Contact & Support.
 */

import type { PaginatedResponse } from '@/modules/company/types';

export type { PaginatedResponse };

/** Individual vs. company lead (mirrors `Domain\ValueObjects\ClientType`). */
export type ClientType = 'individual' | 'company';

/** Project the lead is inquiring about (mirrors `Domain\ValueObjects\ProjectType`). */
export type ProjectType = 'new_website' | 'redesign' | 'ecommerce' | 'landing_page' | 'maintenance' | 'web_app' | 'other';

/** Active catalog row from `GET /api/services/public` or Inertia `serviceOptions`. */
export interface ServiceOption {
    uuid: string;
    name: string;
    slug: string;
}

/** Nested service on list/detail appointment projections. */
export interface AppointmentServiceSummary {
    uuid: string;
    name: string;
    slug: string;
}

/** Sales pipeline stage (mirrors `Domain\ValueObjects\StatusLead`). */
export type StatusLead = 'New' | 'Called' | 'Pending' | 'Declined';

/** Booked-meeting lifecycle; `null` = requested but not yet confirmed. */
export type MeetingStatus = 'Confirmed' | 'Rescheduled' | 'Cancelled' | null;

/** Server-side status filter — active (default) or suspended (soft-deleted). */
export type AppointmentStatus = 'active' | 'suspended';

/** Inbox read/unread filter — maps to the `readed` column. */
export type AppointmentRead = 'read' | 'unread';

/** Spam/ham filter — maps to the `is_spam` anti-spam verdict. */
export type AppointmentSpam = 'spam' | 'ham';

/** A single logged call attempt (`follow_up_calls` JSON entries). */
export interface FollowUpCall {
    at: string;
    note: string;
}

/** A row in the appointments DataTable (lean list projection). */
export interface Appointment {
    uuid: string;
    first_name: string;
    last_name: string;
    client_type: ClientType;
    company_name: string | null;
    project_type: ProjectType | null;
    service?: AppointmentServiceSummary | null;
    email: string;
    phone: string | null;
    status_lead: StatusLead | null;
    meeting_status: MeetingStatus;
    scheduled_at: string | null;
    readed: boolean;
    created_at: string | null;
    deleted_at: string | null;
}

/**
 * The full record returned by the detail render (GET /appointments/{uuid}) —
 * every non-hidden column, including the anti-spam verdict, the follow-up-call
 * log and the free-text notes/owner an operator manages internally.
 */
export interface AppointmentDetail extends Appointment {
    address: string | null;
    address_2: string | null;
    zip_code: string | null;
    city: string | null;
    state: string | null;
    country: string | null;
    country_code: string | null;
    latitude: number | null;
    longitude: number | null;
    sms_consent: boolean;
    is_spam: boolean;
    spam_score: number;
    spam_reasons: string[] | null;
    previous_scheduled_at: string | null;
    follow_up_calls: FollowUpCall[] | null;
    notes: string | null;
    owner: string | null;
    updated_at: string | null;
}

/**
 * Editable projection for the Create/Edit pages — exactly the field set
 * `AppointmentData` accepts. Pipeline state (`status_lead`, `meeting_status`,
 * `scheduled_at`, `readed`, spam verdict) is intentionally absent: it changes
 * only through the dedicated Confirm / Reschedule / Cancel / mark-read actions,
 * never a generic mass-assigned update.
 */
export interface AppointmentEditData {
    uuid: string;
    first_name: string;
    last_name: string;
    client_type: ClientType;
    company_name: string | null;
    service_uuid: string | null;
    email: string;
    phone: string | null;
    address: string | null;
    address_2: string | null;
    zip_code: string | null;
    city: string | null;
    state: string | null;
    country: string | null;
    country_code: string | null;
    latitude: number | null;
    longitude: number | null;
    sms_consent: boolean;
    notes: string | null;
    owner: string | null;
}

/* ── Filters / query state ────────────────────────────────────────────────── */

/** Filters echoed back by the server (mirrors AppointmentFilterData). */
export interface AppointmentFilters {
    search: string | null;
    status: AppointmentStatus | null;
    status_lead: StatusLead | null;
    meeting_status: Exclude<MeetingStatus, null> | null;
    client_type: ClientType | null;
    service_uuid: string | null;
    read: AppointmentRead | null;
    spam: AppointmentSpam | null;
    date_from: string | null;
    date_to: string | null;
    scheduled_from: string | null;
    scheduled_to: string | null;
}

/** The full reactive query state driving the server-side DataTable. */
export interface AppointmentQuery extends AppointmentFilters {
    page: number;
    per_page: number;
}
