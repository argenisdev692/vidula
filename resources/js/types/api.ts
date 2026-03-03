/* ══════════════════════════════════════════════════════════════════
   API Response Contracts — mirrors backend DTOs exactly
   Per ARQUITECTURE-REACT-INERTIA.md — types/api.ts
   ══════════════════════════════════════════════════════════════════ */

// ── Shared Pagination ────────────────────────────────────────────
export interface PaginatedResponse<T> {
  data: T[];
  meta: {
    currentPage: number;
    lastPage: number;
    perPage: number;
    total: number;
  };
}

// ── Export Types ─────────────────────────────────────────────────
export type ExportFormat = 'excel' | 'pdf';

export interface ExportParams {
  format: ExportFormat;
  dateFrom?: string;
  dateTo?: string;
  [key: string]: string | number | boolean | undefined;
}

// ── User Status (mirrors backend enum) ──────────────────────────
export type UserStatus = 'active' | 'suspended' | 'banned' | 'pending_setup';

// ── User List Item (for tables) ─────────────────────────────────
export interface UserListItem {
  id: number;
  uuid: string;
  name: string;
  last_name: string | null;
  full_name: string;
  email: string;
  status: UserStatus;
  roles: string[];
  profile_photo_path: string | null;
  created_at: string; // ISO 8601
}

// ── User Detail ─────────────────────────────────────────────────
export interface UserDetail extends UserListItem {
  username: string | null;
  phone: string | null;
  date_of_birth: string | null;
  address: string | null;
  zip_code: string | null;
  city: string | null;
  state: string | null;
  country: string | null;
  gender: string | null;
  updated_at: string;
}

// ── User Filters ────────────────────────────────────────────────
export interface UserFilters {
  page?: number;
  perPage?: number;
  search?: string;
  status?: UserStatus | '';
  dateFrom?: string;   // ISO 8601 'YYYY-MM-DD'
  dateTo?: string;     // ISO 8601 'YYYY-MM-DD'
  sortBy?: string;
  sortDir?: 'asc' | 'desc';
}

// ── Page Props ──────────────────────────────────────────────────
export interface UsersIndexPageProps {
  filters: UserFilters;
}

// ── Company Data ────────────────────────────────────────────────

export interface CompanyDataListItem {
  id: string; // This corresponds to the UUID
  user_id: number;
  name: string | null;
  company_name: string;
  email: string | null;
  phone: string | null;
  address: string | null;
  website: string | null;
  created_at: string; // ISO 8601
  deleted_at?: string | null;
}

export interface CompanyDataDetail extends CompanyDataListItem {
  facebook_link: string | null;
  instagram_link: string | null;
  linkedin_link: string | null;
  twitter_link: string | null;
  latitude: number | null;
  longitude: number | null;
  signature_path: string | null;
  updated_at: string | null;
  deleted_at: string | null;
}

export interface CreateCompanyDataDTO {
  user_id: number;
  company_name: string;
  name?: string | null;
  email?: string | null;
  phone?: string | null;
  address?: string | null;
  website?: string | null;
  facebook_link?: string | null;
  instagram_link?: string | null;
  linkedin_link?: string | null;
  twitter_link?: string | null;
  latitude?: number | null;
  longitude?: number | null;
  signature_path?: string | null;
}

export interface UpdateCompanyDataDTO {
  companyName: string;
  name?: string | null;
  email?: string | null;
  phone?: string | null;
  address?: string | null;
  website?: string | null;
  facebook?: string | null;
  instagram?: string | null;
  linkedin?: string | null;
  twitter?: string | null;
  latitude?: number | null;
  longitude?: number | null;
}

export interface CompanyDataFilters {
  page?: number;
  perPage?: number;
  search?: string;
  userId?: number;
  dateFrom?: string;   // ISO 8601 'YYYY-MM-DD'
  dateTo?: string;     // ISO 8601 'YYYY-MM-DD'
  sortBy?: string;
  sortDir?: 'asc' | 'desc';
}

export interface CompanyDataIndexPageProps {
  filters: CompanyDataFilters;
}

// ── Authentication ──────────────────────────────────────────────

export interface AuthUser {
  id: number;
  uuid: string;
  name: string;
  last_name: string | null;
  username: string | null;
  email: string;
  email_verified_at: string | null;
  phone: string | null;
  date_of_birth: string | null;
  address: string | null;
  zip_code: string | null;
  city: string | null;
  state: string | null;
  country: string | null;
  gender: string | null;
  profile_photo_path: string | null;
  latitude: number | null;
  longitude: number | null;
  terms_and_conditions: boolean;
  roles: string[];
  permissions: string[];
  created_at: string;
  updated_at: string;
}

export interface LoginPasswordDTO {
  email: string;
  password: string;
}

export interface LoginOtpRequestDTO {
  identifier: string; // email or phone
}

export interface LoginOtpVerifyDTO {
  identifier: string;
  otp: string;
}

export interface ForgotPasswordEmailDTO {
  email: string;
}

export interface ForgotPasswordOtpDTO {
  email: string;
  otp: string;
}

export interface ForgotPasswordResetDTO {
  email: string;
  token: string;
  password: string;
  password_confirmation: string;
}

export interface UpdateProfileDTO {
  name: string;
  last_name: string | null;
  username: string | null;
  email: string;
  phone: string | null;
  date_of_birth: string | null;
  address: string | null;
  zip_code: string | null;
  city: string | null;
  state: string | null;
  country: string | null;
  gender: string | null;
}

export interface UpdatePasswordDTO {
  current_password: string;
  password: string;
  password_confirmation: string;
}

// ── Products ────────────────────────────────────────────────
export interface ProductListItem {
  id: string; // uuid
  user_id: number;
  type: string;
  title: string;
  slug: string;
  status: string;
  price: number;
  currency: string;
  created_at: string;
  deleted_at?: string | null;
}

export interface ProductDetail extends ProductListItem {
  description: string | null;
  thumbnail: string | null;
  level: string;
  language: string;
  updated_at: string | null;
}

export interface ProductFilters {
  page?: number;
  perPage?: number;
  search?: string;
  type?: string;
  status?: string;
  dateFrom?: string;
  dateTo?: string;
}

// ── Clients ────────────────────────────────────────────────
export interface ClientListItem {
  id: string; // uuid
  name: string;
  nif?: string | null;
  email: string | null;
  phone: string | null;
  company: string | null;
  created_at: string;
  deleted_at?: string | null;
}

export interface ClientDetail extends ClientListItem {
  address: string | null;
  tax_id: string | null;
  notes: string | null;
  updated_at: string | null;
}

export interface ClientFilters {
  page?: number;
  perPage?: number;
  search?: string;
  status?: string;
  dateFrom?: string;
  dateTo?: string;
}

// ── Students ────────────────────────────────────────────────
export interface StudentListItem {
  id: string; // uuid
  name: string;
  email: string;
  phone: string | null;
  dni: string | null;
  birth_date: string | null;
  address: string | null;
  avatar: string | null;
  active: boolean;
  created_at: string;
  deleted_at?: string | null;
}

export interface StudentDetail extends StudentListItem {
  notes: string | null;
  updated_at: string | null;
}

export interface CreateStudentDTO {
  name: string;
  email: string;
  phone?: string | null;
  dni?: string | null;
  birth_date?: string | null;
  address?: string | null;
  avatar?: string | null;
  notes?: string | null;
  active?: boolean;
}

export interface UpdateStudentDTO {
  name?: string;
  email?: string;
  phone?: string | null;
  dni?: string | null;
  birth_date?: string | null;
  address?: string | null;
  avatar?: string | null;
  notes?: string | null;
  active?: boolean;
}

export interface StudentFilters {
  page?: number;
  perPage?: number;
  search?: string;
  status?: string; 
  dateFrom?: string;
  dateTo?: string;
}
