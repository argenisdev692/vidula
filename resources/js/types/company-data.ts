/**
 * CompanyData — TypeScript types for the CompanyData module.
 */

export type CompanyStatus = 'active' | 'inactive' | 'pending';

export interface CompanyData {
  uuid: string;
  user_uuid: string;
  company_name: string;
  name: string | null;
  email: string | null;
  phone: string | null;
  address: string | null;
  website: string | null;
  facebook_link: string | null;
  instagram_link: string | null;
  linkedin_link: string | null;
  twitter_link: string | null;
  social_links: {
    facebook?: string;
    instagram?: string;
    linkedin?: string;
    twitter?: string;
    website?: string;
  };
  coordinates: {
    latitude: number | null;
    longitude: number | null;
  };
  latitude: number | null;
  longitude: number | null;
  status: CompanyStatus;
  signature_url: string | null;
  created_at?: string | null;
  updated_at?: string | null;
  deleted_at?: string | null;
}

export interface CreateCompanyDataPayload {
  user_uuid: string;
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

export interface UpdateCompanyDataPayload {
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

export interface CompanyDataFilters {
  page?: number;
  perPage?: number;
  search?: string;
  userUuid?: string;
  dateFrom?: string;
  dateTo?: string;
  sortBy?: string;
  sortDir?: 'asc' | 'desc';
}
