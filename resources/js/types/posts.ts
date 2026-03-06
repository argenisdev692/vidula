export interface PostListItem {
  uuid: string;
  post_title: string;
  post_title_slug: string;
  post_excerpt: string | null;
  post_cover_image: string | null;
  category_name: string | null;
  post_status: 'draft' | 'published' | 'scheduled' | 'archived';
  published_at: string | null;
  scheduled_at: string | null;
  created_at: string;
  updated_at: string;
  deleted_at: string | null;
}

export interface PostDetail extends PostListItem {
  post_content: string;
  meta_title: string | null;
  meta_description: string | null;
  meta_keywords: string | null;
  category_uuid: string | null;
  user_id: number | null;
}

export interface CreatePostPayload {
  post_title: string;
  post_title_slug?: string;
  post_content: string;
  post_excerpt?: string;
  post_cover_image?: string;
  meta_title?: string;
  meta_description?: string;
  meta_keywords?: string;
  category_uuid?: string;
  post_status: 'draft' | 'published' | 'scheduled' | 'archived';
  published_at?: string | null;
  scheduled_at?: string | null;
}

export interface UpdatePostPayload {
  post_title?: string;
  post_title_slug?: string;
  post_content?: string;
  post_excerpt?: string;
  post_cover_image?: string;
  meta_title?: string;
  meta_description?: string;
  meta_keywords?: string;
  category_uuid?: string;
  post_status?: 'draft' | 'published' | 'scheduled' | 'archived';
  published_at?: string | null;
  scheduled_at?: string | null;
}

export interface PostFilters {
  page?: number;
  per_page?: number;
  search?: string;
  status?: 'active' | 'deleted' | 'draft' | 'published' | 'scheduled' | 'archived';
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
