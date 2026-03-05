/**
 * Blog Categories — TypeScript types for the Blog Categories module.
 *
 * All keys use snake_case to match backend Spatie Data output.
 */

export interface BlogCategoryListItem {
  uuid: string;
  blog_category_name: string;
  blog_category_description: string | null;
  blog_category_image: string | null;
  created_at: string;
  updated_at: string;
  deleted_at: string | null;
}

export interface BlogCategoryDetail extends BlogCategoryListItem {
  user_id: number | null;
}

export interface CreateBlogCategoryPayload {
  blog_category_name: string;
  blog_category_description?: string;
  blog_category_image?: string;
}

export interface UpdateBlogCategoryPayload {
  blog_category_name?: string;
  blog_category_description?: string;
  blog_category_image?: string;
}

export interface BlogCategoryFilters {
  page?: number;
  per_page?: number;
  search?: string;
  status?: 'active' | 'deleted';
  date_from?: string;
  date_to?: string;
  sort_by?: string;
  sort_dir?: 'asc' | 'desc';
}

export interface PaginatedResponse<T> {
  data: T[];
  meta: {
    currentPage: number;
    lastPage: number;
    perPage: number;
    total: number;
  };
}
