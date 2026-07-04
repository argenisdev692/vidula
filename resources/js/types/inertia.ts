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

export interface SharedProps {
    auth: AuthProps;
    flash: FlashProps;
    [key: string]: unknown;
}
