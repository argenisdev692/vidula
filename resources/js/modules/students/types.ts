/**
 * Students module — snake_case interfaces mirroring
 * StudentEloquentModel list/detail projections (global catalog, no owner).
 */

import type { PaginatedResponse } from '@/modules/company/types';

export type { PaginatedResponse };

/** Domain lifecycle status (distinct from soft-delete Active/Suspended). */
export type StudentLifecycleStatus = 'DRAFT' | 'ACTIVE' | 'ARCHIVED';

/** Soft-delete list filter. */
export type StudentSoftStatus = 'active' | 'suspended';

/** A row in the students DataTable. */
export interface Student {
    uuid: string;
    name: string;
    email: string | null;
    phone: string | null;
    dni: string | null;
    address: string | null;
    avatar: string | null;
    notes: string | null;
    status: StudentLifecycleStatus;
    active: boolean;
    created_at: string | null;
    deleted_at: string | null;
}

/** Filters echoed back by the server (mirrors StudentFilterData). */
export interface StudentFilters {
    search: string | null;
    status: StudentSoftStatus | null;
    student_status: StudentLifecycleStatus | null;
    active: boolean | null;
    date_from: string | null;
    date_to: string | null;
}

/** Reactive query state driving the server-side DataTable. */
export interface StudentQuery extends StudentFilters {
    page: number;
    per_page: number;
}
