/**
 * Blog module — snake_case interfaces mirroring the backend
 * {@link \Modules\Blog\Infrastructure\Persistence\Eloquent\Models\BlogCategoryEloquentModel}
 * projection. The list query selects a lean column set + the appended
 * `image_url` and the `user:id,first_name,last_name` relation; `updated_at` is
 * intentionally absent from list rows.
 *
 * A row is ACTIVE when `deleted_at === null` and SUSPENDED (soft-deleted) when
 * it holds a timestamp — the list is homogeneous per the `status` filter.
 */

import type { PaginatedResponse } from '@/modules/company/types';

export type { PaginatedResponse };

/** The lean author projection loaded alongside each row. */
export interface BlogCategoryAuthor {
    id: number;
    first_name: string | null;
    last_name: string | null;
}

/** A row in the blog-categories DataTable. */
export interface BlogCategory {
    uuid: string;
    blog_category_name: string | null;
    blog_category_description: string | null;
    blog_category_image: string | null;
    image_url: string | null;
    user_id: number;
    user?: BlogCategoryAuthor | null;
    created_at: string | null;
    deleted_at: string | null;
}

/** Active = non-trashed (default); Suspended = soft-deleted (onlyTrashed). */
export type BlogCategoryStatus = 'active' | 'suspended';

/** Filters echoed back by the server (mirrors BlogCategoryFilterData). */
export interface BlogCategoryFilters {
    search: string | null;
    status: BlogCategoryStatus | null;
    date_from: string | null;
    date_to: string | null;
}

/** The full reactive query state driving the server-side DataTable. */
export interface BlogCategoryQuery extends BlogCategoryFilters {
    page: number;
    per_page: number;
}
