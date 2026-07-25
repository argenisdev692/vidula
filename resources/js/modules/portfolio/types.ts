/**
 * Portfolio module — snake_case interfaces mirroring the backend
 * {@link \Modules\Portfolio\Infrastructure\Persistence\Eloquent\Models\PortfolioEloquentModel}
 * (+ its `gallery` HasMany child, {@link PortfolioGalleryImage}). The list query
 * selects a lean column set + `withCount('gallery')`; the Show detail loads the
 * full model plus the ordered `gallery` relation (see GetPortfolioHandler /
 * EloquentPortfolioRepository) — `description`, `updated_at` and `gallery` are
 * therefore only guaranteed on the Show payload, not on list rows.
 *
 * A row is ACTIVE when `deleted_at === null` and SUSPENDED (soft-deleted) when
 * it holds a timestamp — independent of `is_public`, the landing-page
 * visibility flag.
 */

import type { PaginatedResponse } from '@/modules/company/types';

export type { PaginatedResponse };

/** The lean author projection loaded alongside each row. */
export interface PortfolioAuthor {
    id: number;
    first_name: string | null;
    last_name: string | null;
}

/** One gallery image, ordered by `sort_order` (drag-reorder drives this). */
export interface PortfolioGalleryImage {
    uuid: string;
    url: string | null;
    sort_order: number;
}

/** A row in the portfolios DataTable / the Show detail payload. */
export interface Portfolio {
    uuid: string;
    title: string;
    client_name: string;
    project_type: string;
    /** Tech badges for Astro / admin (e.g. React, Next.js, PostgreSQL). */
    tech_stack: string[] | null;
    live_url: string | null;
    published_at: string | null;
    is_public: boolean;
    cover_path: string | null;
    video_path: string | null;
    cover_url: string | null;
    video_url: string | null;
    description?: string | null;
    sort_order: number;
    user_id: number;
    user?: PortfolioAuthor | null;
    /** List rows only (`withCount('gallery')`). */
    gallery_count?: number;
    /** Show detail only (`with('gallery')`), ordered by `sort_order`. */
    gallery?: PortfolioGalleryImage[];
    created_at: string | null;
    updated_at?: string | null;
    deleted_at: string | null;
}

/** Active = non-trashed (default); Suspended = soft-deleted (onlyTrashed). */
export type PortfolioStatus = 'active' | 'suspended';

/** Filters echoed back by the server (mirrors PortfolioFilterData). */
export interface PortfolioFilters {
    search: string | null;
    status: PortfolioStatus | null;
    date_from: string | null;
    date_to: string | null;
}

/** The full reactive query state driving the server-side DataTable. */
export interface PortfolioQuery extends PortfolioFilters {
    page: number;
    per_page: number;
}
