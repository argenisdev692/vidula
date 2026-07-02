// Minimal ambient types for the Google Maps `places` SDK loaded at runtime by
// GoogleMapsLoaderService. Only the surface we actually use is declared.
declare namespace google {
  namespace maps {
    namespace places {
      interface AutocompleteOptions {
        types?: string[];
        fields?: string[];
        componentRestrictions?: { country?: string | string[] };
      }
      class Autocomplete {
        constructor(input: HTMLInputElement, opts?: AutocompleteOptions);
        addListener(eventName: string, handler: () => void): MapsEventListener;
        getPlace(): PlaceResult;
      }
      interface AddressComponent {
        long_name: string;
        short_name: string;
        types: string[];
      }
      interface PlaceResult {
        name?: string;
        formatted_address?: string;
        address_components?: AddressComponent[];
        geometry?: {
          location?: {
            lat(): number;
            lng(): number;
          };
        };
      }
    }
    interface MapsEventListener {
      remove(): void;
    }
    namespace event {
      function removeListener(listener: MapsEventListener): void;
      function clearInstanceListeners(instance: object): void;
    }
  }
}
