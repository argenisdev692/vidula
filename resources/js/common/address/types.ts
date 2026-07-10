/**
 * Reusable address value shared by every consumer of {@link AddressAutocomplete}
 * (auth profile, users module, …). snake_case keys mirror the backend columns
 * (`address`, `address_2`, `zip_code`, `city`, `state`, `country`, `latitude`,
 * `longitude`). Every text field is nullable; coordinates are numeric and filled
 * silently from the selected Google place.
 */
export interface AddressValue {
    address: string | null;
    address_2: string | null;
    zip_code: string | null;
    city: string | null;
    state: string | null;
    country: string | null;
    /**
     * ISO-3166 alpha-2 code (e.g. `US`) derived from the selected place's
     * `country` short text. Optional: only consumers whose backend stores a
     * `country_code` column opt in via `AddressAutocomplete`'s `withCountryCode`
     * prop — omit it and the field is neither shown nor bound.
     */
    country_code?: string | null;
    latitude: number | null;
    longitude: number | null;
}

/** Per-field validation messages keyed by an {@link AddressValue} field. */
export type AddressErrors = Partial<Record<keyof AddressValue, string>>;

/**
 * Fields Google fills from a selected place and the UI keeps read-only by
 * default. `address` (the autocomplete input) and `address_2` (always manual)
 * are intentionally excluded. `country_code` is opt-in (see `withCountryCode`).
 */
export type ManagedAddressField = 'zip_code' | 'city' | 'state' | 'country' | 'country_code';

/** A pristine, fully-null address — safe default for `useForm` initial state. */
export const EMPTY_ADDRESS: AddressValue = {
    address: null,
    address_2: null,
    zip_code: null,
    city: null,
    state: null,
    country: null,
    country_code: null,
    latitude: null,
    longitude: null,
};
