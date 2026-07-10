/**
 * Profile read model — mirrors Modules\Auth\Application\DTOs\ProfileData
 * (snake_case output via SnakeCaseMapper). Consumed by pages/Auth/Profile.vue.
 */
export interface ProfileData {
    uuid: string;
    first_name: string;
    last_name: string | null;
    username: string | null;
    email: string;
    phone: string | null;
    date_of_birth: string | null;
    gender: string | null;
    address: string | null;
    address_2: string | null;
    city: string | null;
    state: string | null;
    zip_code: string | null;
    country: string | null;
    country_code: string | null;
    latitude: number | null;
    longitude: number | null;
    email_verified: boolean;
    two_factor_enabled: boolean;
    created_at: string | null;
    profile_photo_url: string | null;
    roles: string[];
    permissions: string[];
}

/**
 * One active browser session (mirrors SessionRegistry::listForUser). `is_current`
 * flags the device the page is being viewed from — it can never be revoked
 * individually, only via "sign out everywhere".
 */
export interface ActiveSession {
    id: string;
    ip_address: string | null;
    user_agent: string | null;
    last_active_at: string;
    is_current: boolean;
}

/**
 * A device that skips the two-factor step for 30 days (mirrors the
 * `trusted_devices` rows selected in ProfileController::edit).
 */
export interface TrustedDevice {
    uuid: string;
    user_agent: string | null;
    ip_address: string | null;
    last_used_at: string | null;
    expires_at: string;
}

/**
 * Two-factor state. `enabled` = a secret exists (enrollment may be pending);
 * `confirmed` = the user completed the challenge and 2FA is fully active.
 */
export interface TwoFactorState {
    enabled: boolean;
    confirmed: boolean;
}

/**
 * Editable subset submitted to Fortify's PUT /user/profile-information. All
 * fields are strings (empty string = cleared) so the form binds cleanly; the
 * page transforms empties to `null` before submit.
 */
export interface ProfileFormValues {
    first_name: string;
    last_name: string;
    username: string;
    email: string;
    phone: string;
    date_of_birth: string;
    gender: string;
    address: string;
    address_2: string;
    city: string;
    state: string;
    zip_code: string;
    country: string;
    latitude: number | null;
    longitude: number | null;
}
