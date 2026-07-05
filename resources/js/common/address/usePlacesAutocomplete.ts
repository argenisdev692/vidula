import { ref, shallowRef } from 'vue';
import { parseAddressComponents } from './parseAddressComponents';
import type { AddressValue } from './types';
import { loadGoogleMaps } from './useGoogleMaps';

/** A single autocomplete prediction, flattened for rendering + selection. */
export interface AddressSuggestion {
    id: string;
    primary: string;
    secondary: string;
    prediction: google.maps.places.PlacePrediction;
}

/** The minimum characters before we spend an autocomplete request. */
const MIN_QUERY_LENGTH = 3;

/**
 * Places (New) Data API composable: manages the billable session token, fetches
 * predictions for a query, and resolves a chosen prediction into a flat
 * {@link AddressValue} patch (address parts + coordinates). Returns reactive
 * state the component renders; it owns no DOM and injects no Google widget, so
 * the surrounding UI stays fully token-styled.
 */
export function usePlacesAutocomplete(apiKey: string) {
    const suggestions = ref<AddressSuggestion[]>([]);
    const loading = ref(false);
    const failed = ref(false);

    const library = shallowRef<google.maps.places.PlacesLibrary | null>(null);
    let sessionToken: google.maps.places.AutocompleteSessionToken | null = null;

    async function ensureLibrary(): Promise<google.maps.places.PlacesLibrary> {
        if (library.value === null) {
            await loadGoogleMaps(apiKey);
            library.value = await window.google.maps.importLibrary('places');
        }

        return library.value;
    }

    async function search(input: string): Promise<void> {
        const query = input.trim();

        if (query.length < MIN_QUERY_LENGTH) {
            suggestions.value = [];
            return;
        }

        loading.value = true;
        failed.value = false;

        try {
            const lib = await ensureLibrary();
            sessionToken ??= new lib.AutocompleteSessionToken();

            const { suggestions: results } = await lib.AutocompleteSuggestion.fetchAutocompleteSuggestions({
                input: query,
                sessionToken,
            });

            suggestions.value = results
                .map((result) => result.placePrediction)
                .filter((prediction): prediction is google.maps.places.PlacePrediction => prediction !== null)
                .map((prediction) => ({
                    id: prediction.placeId,
                    primary: prediction.mainText?.toString() ?? prediction.text.toString(),
                    secondary: prediction.secondaryText?.toString() ?? '',
                    prediction,
                }));
        } catch {
            failed.value = true;
            suggestions.value = [];
        } finally {
            loading.value = false;
        }
    }

    /**
     * Fetch full details for a chosen suggestion and map them to an address
     * patch. Closes the billing session so the next keystroke opens a new one.
     */
    async function resolve(suggestion: AddressSuggestion): Promise<Partial<AddressValue>> {
        const place = suggestion.prediction.toPlace();
        await place.fetchFields({ fields: ['addressComponents', 'location', 'formattedAddress'] });
        sessionToken = null;

        return {
            ...parseAddressComponents(place.addressComponents ?? []),
            latitude: place.location?.lat() ?? null,
            longitude: place.location?.lng() ?? null,
        };
    }

    function clear(): void {
        suggestions.value = [];
    }

    return { suggestions, loading, failed, search, resolve, clear };
}
