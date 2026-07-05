import type { AddressValue } from './types';

/** The address-only slice a place selection can derive (coords come separately). */
type ParsedAddress = Pick<AddressValue, 'address' | 'zip_code' | 'city' | 'state' | 'country'>;

/**
 * Convert the new Places API `addressComponents` array into our flat
 * {@link AddressValue} shape. Only `address`, `zip_code`, `city`, `state` and
 * `country` are derived here; `latitude`/`longitude` come from the place
 * `location` and are merged by the caller. Any component Google omits stays
 * `null`, so the UI can offer a manual unlock for it.
 */
export function parseAddressComponents(components: readonly google.maps.places.AddressComponent[]): ParsedAddress {
    const long = (type: string): string | null =>
        components.find((component) => component.types.includes(type))?.longText ?? null;

    const address = [long('street_number'), long('route')].filter(Boolean).join(' ') || null;

    const zipMain = long('postal_code');
    const zipSuffix = long('postal_code_suffix');
    const zip_code = zipMain !== null && zipSuffix !== null ? `${zipMain}-${zipSuffix}` : zipMain;

    // Locality naming varies by country — fall back through the common variants.
    const city = long('locality') ?? long('postal_town') ?? long('sublocality') ?? long('sublocality_level_1');

    return {
        address,
        zip_code,
        city,
        state: long('administrative_area_level_1'),
        country: long('country'),
    };
}
