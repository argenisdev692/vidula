/**
 * Availability module — snake_case interfaces mirroring the backend
 * {@link \Modules\Availability\Infrastructure\Persistence\Eloquent\Models\AvailabilityRuleEloquentModel}
 * and {@link \Modules\Availability\...\AvailabilityExceptionEloquentModel} rows,
 * serialized verbatim by the length-aware paginator (no Resource layer).
 *
 * Two entities live here:
 *   · Rules      — the recurring weekly template (one slot on one weekday).
 *   · Exceptions — per-date overrides (a closure, or a forced-open window).
 *
 * A row is ACTIVE when `deleted_at === null` and SUSPENDED (soft-deleted) when it
 * holds a timestamp — each list is homogeneous per its `status` filter.
 */

import type { PaginatedResponse } from '@/modules/company/types';

export type { PaginatedResponse };

/** Active = non-trashed (default); Suspended = soft-deleted (onlyTrashed). */
export type AvailabilityStatus = 'active' | 'suspended';

/* ── Weekly rules ─────────────────────────────────────────────────────────── */

/** Narrows the list by the `is_available` flag (mirrors the backend filter). */
export type RuleAvailability = 'available' | 'unavailable';

/** A row in the weekly-rules DataTable. Times arrive as `HH:MM:SS`. */
export interface AvailabilityRule {
    uuid: string;
    day_of_week: number; // 0 (Sunday) … 6 (Saturday), Carbon-aligned
    start_time: string;
    end_time: string;
    is_available: boolean;
    created_at: string | null;
    updated_at?: string | null;
    deleted_at: string | null;
}

/** Filters echoed back by the server (mirrors AvailabilityRuleFilterData). */
export interface AvailabilityRuleFilters {
    day_of_week: number | null;
    availability: RuleAvailability | null;
    status: AvailabilityStatus | null;
}

/** The full reactive query state driving the rules server-side DataTable. */
export interface AvailabilityRuleQuery extends AvailabilityRuleFilters {
    page: number;
    per_page: number;
}

/* ── Date exceptions ──────────────────────────────────────────────────────── */

/** Narrows the list to forced-open (`open`) vs closed (`closed`) days. */
export type ExceptionAvailability = 'open' | 'closed';

/** Provenance — a user override (`manual`) or a system-materialised holiday. */
export type ExceptionSource = 'manual' | 'holiday';

/** A row in the date-exceptions DataTable. `date` arrives as an ISO datetime. */
export interface AvailabilityException {
    uuid: string;
    date: string;
    is_available: boolean;
    start_time: string | null;
    end_time: string | null;
    reason: string | null;
    source: ExceptionSource;
    created_at: string | null;
    updated_at?: string | null;
    deleted_at: string | null;
}

/** Filters echoed back by the server (mirrors AvailabilityExceptionFilterData). */
export interface AvailabilityExceptionFilters {
    availability: ExceptionAvailability | null;
    status: AvailabilityStatus | null;
    date_from: string | null;
    date_to: string | null;
}

/** The full reactive query state driving the exceptions server-side DataTable. */
export interface AvailabilityExceptionQuery extends AvailabilityExceptionFilters {
    page: number;
    per_page: number;
}
