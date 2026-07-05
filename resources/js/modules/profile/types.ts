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
    email_verified: boolean;
    two_factor_enabled: boolean;
    created_at: string | null;
    profile_photo_url: string | null;
    roles: string[];
    permissions: string[];
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
}
