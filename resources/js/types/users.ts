/**
 * Users — TypeScript types for the Users module.
 */

export type UserStatus = 'active' | 'suspended' | 'banned' | 'deleted' | 'pending_setup';

export interface UserListItem {
  uuid: string;
  name: string;
  last_name: string;
  full_name: string;
  email: string;
  username: string | null;
  phone: string | null;
  status: UserStatus;
  profile_photo_path: string | null;
  created_at: string;
  updated_at: string;
  deleted_at?: string | null;
  role: string | null;
  // Address info
  address?: string | null;
  city?: string | null;
  state?: string | null;
  country?: string | null;
  zip_code?: string | null;
}

export interface UserDetail extends UserListItem {
  bio?: string | null;
  social_links?: {
    facebook?: string;
    instagram?: string;
    linkedin?: string;
    twitter?: string;
  };
}

export interface CreateUserPayload {
  name: string;
  last_name: string;
  email: string;
  username?: string;
  phone?: string;
  role: string;
}

export interface UpdateUserPayload {
  name?: string;
  last_name?: string;
  email?: string;
  username?: string;
  phone?: string;
  address?: string;
  city?: string;
  state?: string;
  country?: string;
  zip_code?: string;
}

export interface UserFilters {
  page?: number;
  per_page?: number;
  search?: string;
  status?: UserStatus;
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
