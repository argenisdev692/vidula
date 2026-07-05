/**
 * Inertia v3 shared-prop contract — mirrors HandleInertiaRequests::share().
 * Consumed via usePage<SharedProps>() (Pages/ + module composable layers only).
 */

export interface AuthUser {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
}

export interface AuthProps {
    user: AuthUser | null;
    profile_photo_url: string | null;
    /** Flat list of every permission name the user holds (Spatie getAllPermissions). */
    permissions: string[];
    /** Role names the user holds (Spatie getRoleNames). */
    roles: string[];
}

export interface FlashProps {
    success: string | null;
    error: string | null;
}

/** DB-driven company branding shared on every page (HandleInertiaRequests). */
export interface CompanyBranding {
    name: string;
    /** Light logo (dark text) — for light backgrounds. */
    logo_url: string;
    /** White logo — for dark backgrounds (hero, email header). */
    logo_white_url: string;
    /** Compact mark/icon. */
    mark_url: string;
}

/** Non-secret runtime config exposed to the SPA (HandleInertiaRequests). */
export interface ConfigProps {
    /** Public, referrer-restricted Google Maps JS key for address autocomplete. */
    google_maps_api_key: string;
}

export interface SharedProps {
    auth: AuthProps;
    flash: FlashProps;
    company: CompanyBranding;
    config: ConfigProps;
    [key: string]: unknown;
}
