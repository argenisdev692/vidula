export interface PermissionOption {
  uuid: string;
  name: string;
  guard_name: string;
}

export interface RoleListItem {
  uuid: string;
  name: string;
  guard_name: string;
  permissions: string[];
  users_count: number;
  created_at: string | null;
  updated_at: string | null;
  deleted_at?: string | null;
}

export interface RoleDetail extends RoleListItem {
  available_permissions: PermissionOption[];
}

export interface CreateRolePayload {
  name: string;
  guard_name: string;
  permissions: string[];
}

export interface UpdateRolePayload {
  name?: string;
  guard_name?: string;
  permissions?: string[];
}

export interface RoleFilters {
  page?: number;
  per_page?: number;
  search?: string;
  guard_name?: string;
  sort_by?: 'name' | 'created_at' | 'updated_at';
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

export interface RolesCreatePageProps {
  available_permissions: PermissionOption[];
}

export interface RolePageProps {
  role: RoleDetail;
}
