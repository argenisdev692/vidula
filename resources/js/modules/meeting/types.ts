/**
 * Meeting module — snake_case interfaces mirroring the backend
 * `MeetingEloquentModel` projection. A meeting has one organizer (staff) and
 * any number of polymorphic attendees (`user` | `lead` | `contact` — see
 * `Domain\ValueObjects\AttendeeType`). Distinct from Appointment (the public
 * lead-intake pipeline): Meeting owns the only calendar surface and overlays
 * Appointment events read-only via the `calendar-feed` endpoint.
 */

import type { PaginatedResponse } from '@/modules/company/types';

export type { PaginatedResponse };

/** Meeting lifecycle — no recurrence, single occurrence only. */
export type MeetingStatus = 'Scheduled' | 'Cancelled';

/** The three eligible attendee sources (mirrors `Domain\ValueObjects\AttendeeType`). */
export type AttendeeType = 'user' | 'lead' | 'contact';

/** A resolved attendee reference — minimal fields only (never a full record). */
export interface MeetingAttendeeOption {
    type: AttendeeType;
    uuid: string;
    label: string;
}

/** An attendee already attached to a meeting, as serialized in `MeetingEloquentModel::attendees`. */
export interface MeetingAttendee {
    attendable_type: AttendeeType;
    attendable_id: number;
}

/** A row in the meetings DataTable (lean list projection). */
export interface Meeting {
    uuid: string;
    title: string;
    description: string | null;
    starts_at: string;
    ends_at: string;
    status: MeetingStatus;
    meet_link?: string | null;
    attendees_count: number;
    organizer: { uuid: string; first_name: string; last_name: string } | null;
    created_at: string | null;
    deleted_at: string | null;
}

/** The full record returned by the detail render (GET /meetings/{uuid}). */
export interface MeetingDetail extends Meeting {
    meet_link: string | null;
    attendees: MeetingAttendee[];
    updated_at: string | null;
}

/** Optional seed data for the create form (lead bridge / calendar dateClick). */
export interface MeetingPrefill {
    title?: string;
    starts_at?: string;
    ends_at?: string;
    attendees?: MeetingAttendeeOption[];
}

/** Editable projection for the Create/Edit form payload. */
export interface MeetingEditData {
    uuid: string;
    title: string;
    description: string | null;
    starts_at: string;
    ends_at: string;
    attendees: MeetingAttendeeOption[];
}

/** A single combined calendar entry (own meetings + read-only Appointment overlay). */
export interface CalendarEvent {
    uuid: string;
    title: string;
    start: string;
    end: string;
    source: 'meeting' | 'appointment';
    status: string | null;
    url: string;
}

/* ── Filters / query state ────────────────────────────────────────────────── */

/** Server-side status filter — active (default) or suspended (soft-deleted). */
export type MeetingRowStatus = 'active' | 'suspended';

/** Filters echoed back by the server (mirrors MeetingFilterData). */
export interface MeetingFilters {
    search: string | null;
    status: MeetingRowStatus | null;
    meeting_status: MeetingStatus | null;
    date_from: string | null;
    date_to: string | null;
    starts_from: string | null;
    starts_to: string | null;
}

/** The full reactive query state driving the server-side DataTable. */
export interface MeetingQuery extends MeetingFilters {
    page: number;
    per_page: number;
}
