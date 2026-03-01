/**
 * CompanyData — TypeScript types for the CompanyData module.
 */

export type CompanyStatus = 'active' | 'inactive' | 'pending';

export interface CompanyData {
  uuid: string;
  userUuid: string;
  companyName: string;
  name: string | null;
  email: string | null;
  phone: string | null;
  address: string | null;
  socialLinks: {
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
  status: CompanyStatus;
  signatureUrl: string | null;
}

export interface CreateCompanyDataPayload {
  userUuid: string;
  companyName: string;
  email?: string;
  phone?: string;
  address?: string;
}

export interface UpdateCompanyDataPayload {
  companyName: string;
  name?: string;
  email?: string;
  phone?: string;
  address?: string;
  website?: string;
  facebook?: string;
  instagram?: string;
  linkedin?: string;
  twitter?: string;
  latitude?: number;
  longitude?: number;
}

export interface CompanyDataFilters {
  page?: number;
  perPage?: number;
  search?: string;
  userUuid?: string;
  sortBy?: string;
  sortDir?: 'asc' | 'desc';
}
