/**
 * Minimal ambient typings for the slice of the Google Maps JavaScript API
 * (Places "New" Data API) consumed by `common/address`. We declare only what we
 * actually call so the project stays dependency-free (no `@types/google.maps`)
 * while keeping strict TypeScript happy.
 *
 * @see https://developers.google.com/maps/documentation/javascript/place-autocomplete-data
 */
declare namespace google.maps {
    function importLibrary(library: 'places'): Promise<places.PlacesLibrary>;
    function importLibrary(library: string): Promise<unknown>;

    interface LatLng {
        lat(): number;
        lng(): number;
    }

    namespace places {
        /** Shape returned by `importLibrary('places')`. */
        interface PlacesLibrary {
            AutocompleteSessionToken: typeof AutocompleteSessionToken;
            AutocompleteSuggestion: typeof AutocompleteSuggestion;
        }

        /** Groups billable autocomplete + details requests into one session. */
        class AutocompleteSessionToken {}

        /** One structured component of a resolved address. */
        interface AddressComponent {
            longText: string | null;
            shortText: string | null;
            types: string[];
        }

        interface FetchFieldsRequest {
            fields: string[];
        }

        class Place {
            addressComponents?: AddressComponent[] | null;
            location?: LatLng | null;
            formattedAddress?: string | null;
            fetchFields(request: FetchFieldsRequest): Promise<{ place: Place }>;
        }

        interface FormattableText {
            text: string;
            toString(): string;
        }

        class PlacePrediction {
            placeId: string;
            text: FormattableText;
            mainText: FormattableText | null;
            secondaryText: FormattableText | null;
            toPlace(): Place;
        }

        class AutocompleteSuggestion {
            placePrediction: PlacePrediction | null;
            static fetchAutocompleteSuggestions(
                request: AutocompleteRequest,
            ): Promise<{ suggestions: AutocompleteSuggestion[] }>;
        }

        interface AutocompleteRequest {
            input: string;
            sessionToken?: AutocompleteSessionToken;
            includedRegionCodes?: string[];
            language?: string;
            region?: string;
        }
    }
}

interface Window {
    google: typeof google;
}
