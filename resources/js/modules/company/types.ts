/**
 * Company settings module — snake_case interfaces mirroring the backend
 * `company_data` row (a seeded singleton) and its Inertia payloads. Asset columns
 * (`*_path`) hold R2 object keys; resolved brand URLs arrive separately on the
 * shared `company` prop (see {@link CompanyBranding} in `@/types/inertia`).
 */

/** The single company record as serialized by the Eloquent model. */
export interface CompanyData {
    uuid: string;
    name: string | null;
    company_name: string;
    signature_path: string | null;
    logo_path: string | null;
    logo_white_path: string | null;
    mark_path: string | null;
    email: string | null;
    phone: string | null;
    address: string | null;
    address_2: string | null;
    website: string | null;
    facebook_link: string | null;
    instagram_link: string | null;
    linkedin_link: string | null;
    twitter_link: string | null;
    tiktok_link: string | null;
    latitude: number | null;
    longitude: number | null;
    created_at: string | null;
    updated_at: string | null;
    deleted_at: string | null;
}

/** The three interchangeable brand logo variants. */
export type CompanyLogoType = 'logo' | 'logo_white' | 'mark';

/** Laravel length-aware paginator envelope (the singleton lives at `data[0]`). */
export interface PaginatedResponse<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}
