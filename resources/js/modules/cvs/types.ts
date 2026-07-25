/**
 * Cvs module — snake_case interfaces mirroring CvEloquentModel projections.
 */

import type { PaginatedResponse } from '@/modules/company/types';

export type { PaginatedResponse };

/** Lean owner projection loaded alongside each row. */
export interface CvOwner {
    id: number;
    first_name: string | null;
    last_name: string | null;
}

export type CvNiche = 'fullstack' | 'other';

export type CvFileType = 'pdf' | 'md';

/** Soft-delete list filter. */
export type CvSoftStatus = 'active' | 'suspended';

/** A row in the CVs DataTable. */
export interface Cv {
    uuid: string;
    title: string;
    niche: CvNiche;
    is_primary: boolean;
    file_type: CvFileType;
    original_filename: string;
    raw_text?: string | null;
    download_url?: string | null;
    user_id: number;
    user?: CvOwner | null;
    created_at: string | null;
    updated_at?: string | null;
    deleted_at: string | null;
}

/** Filters echoed back by the server (mirrors CvFilterData). */
export interface CvFilters {
    search: string | null;
    status: CvSoftStatus | null;
    niche: CvNiche | null;
    date_from: string | null;
    date_to: string | null;
}

/** Reactive query state driving the server-side DataTable. */
export interface CvQuery extends CvFilters {
    page: number;
    per_page: number;
}
