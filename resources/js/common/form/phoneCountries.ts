import { getCountries, getCountryCallingCode } from 'libphonenumber-js';
import type { CountryCode } from 'libphonenumber-js';

/** One selectable country row for {@link PhoneField}'s country dropdown. */
export interface PhoneCountry {
    /** ISO-3166 alpha-2 code, e.g. `US`. */
    code: CountryCode;
    /** Localised country name, e.g. `United States`. */
    name: string;
    /** Dial code without the `+`, e.g. `1`. */
    callingCode: string;
    /** Regional-indicator emoji flag, e.g. 🇺🇸 (no image asset needed). */
    flag: string;
    /** `"United States +1"` — the string the Volt Select filters against. */
    label: string;
}

const regionNames = new Intl.DisplayNames(['en'], { type: 'region' });

/** Turn an ISO code into its regional-indicator emoji flag. */
function flagEmoji(code: string): string {
    return code.replace(/./g, (char) => String.fromCodePoint(127397 + char.charCodeAt(0)));
}

let cache: PhoneCountry[] | null = null;

/**
 * Build the full country list from libphonenumber-js, sorted by name. Computed
 * once and memoised — the list is static for the session.
 */
export function buildPhoneCountries(): PhoneCountry[] {
    if (cache !== null) {
        return cache;
    }

    cache = getCountries()
        .map((code): PhoneCountry => {
            const callingCode = getCountryCallingCode(code);
            const name = regionNames.of(code) ?? code;

            return { code, name, callingCode, flag: flagEmoji(code), label: `${name} +${callingCode}` };
        })
        .sort((a, b) => a.name.localeCompare(b.name));

    return cache;
}
