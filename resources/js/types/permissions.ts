export interface RoleOption {
  uuid: string;
  name: string;
  guard_name: string;
}

export interface PermissionListItem {
  uuid: string;
  name: string;
  guard_name: string;
  roles: string[];
  roles_count: number;
  created_at: string | null;
  updated_at: string | null;
  deleted_at?: string | null;
}

export interface PermissionDetail extends PermissionListItem {
  available_roles: RoleOption[];
}

export interface CreatePermissionPayload {
  name: string;
  guard_name: string;
  roles: string[];
}

export interface UpdatePermissionPayload {
  name?: string;
  guard_name?: string;
  roles?: string[];
}

export interface PermissionFilters {
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

export interface PermissionsCreatePageProps {
  available_roles: RoleOption[];
}

export interface PermissionPageProps {
  permission: PermissionDetail;
}
