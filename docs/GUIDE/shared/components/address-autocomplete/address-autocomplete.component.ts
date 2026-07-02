import {
  AfterViewInit,
  Component,
  DestroyRef,
  ElementRef,
  forwardRef,
  inject,
  input,
  output,
  signal,
  viewChild,
} from '@angular/core';
import { ControlValueAccessor, NG_VALUE_ACCESSOR } from '@angular/forms';
import { GoogleMapsLoaderService } from '../../services/google-maps-loader.service';

/** Structured result emitted when the user picks a Google Maps suggestion. */
/**
 * What the {@link ControlValueAccessor} value (and `AddressSelection.address`)
 * should hold: just the street line, or the complete Google-formatted address.
 * Use `'street'` when the form has separate city/state/zip fields, `'full'`
 * when there is a single address field.
 */
export type AddressValueMode = 'street' | 'full';

export interface AddressSelection {
  /** The value written to the control — street line or full address per valueMode. */
  address: string;
  city: string;
  state: string;
  zipcode: string;
  country: string;
  latitude: number | null;
  longitude: number | null;
  /** The full Google-formatted address, for reference/logging. */
  formattedAddress: string;
}

/**
 * Reusable Google Maps address autocomplete built on the Places `Autocomplete`
 * widget. Acts as a {@link ControlValueAccessor} for the street-address string
 * (use with `formControlName`/`[(ngModel)]`) and additionally emits a structured
 * {@link AddressSelection} via `addressSelected` so the parent can fill the
 * read-only city/state/zip/country/lat/lng fields.
 *
 * The suggestion dropdown is Google's `.pac-container`, styled globally in
 * `styles.css` to match the app's input design tokens.
 */
@Component({
  selector: 'app-address-autocomplete',
  imports: [],
  providers: [
    {
      provide: NG_VALUE_ACCESSOR,
      useExisting: forwardRef(() => AddressAutocompleteComponent),
      multi: true,
    },
  ],
  template: `
    <input
      #addressInput
      [id]="inputId()"
      type="text"
      class="form-input"
      autocomplete="off"
      [placeholder]="placeholder()"
      [value]="value()"
      [disabled]="disabled()"
      (input)="onInput($event)"
      (blur)="onTouched()" />
    @if (mapsLoading()) {
      <span class="maps-hint"><i class="pi pi-spin pi-spinner"></i> Loading maps…</span>
    }
    @if (mapsError()) {
      <span class="maps-hint maps-hint--error">{{ mapsError() }}</span>
    }
  `,
  styles: [
    `
      :host {
        display: flex;
        flex-direction: column;
        gap: var(--space-2);
      }
      .form-input {
        width: 100%;
        height: var(--input-height);
        background: var(--input-bg);
        border: 1px solid var(--input-border);
        border-radius: var(--input-radius);
        color: var(--text-primary);
        font-family: var(--font-sans);
        font-size: var(--text-sm);
        padding: 0 var(--space-3);
        outline: none;
        transition: border-color var(--transition), box-shadow var(--transition);
      }
      .form-input:focus {
        border-color: var(--input-border-focus);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent-primary) 14%, transparent);
      }
      .form-input::placeholder {
        color: var(--text-muted);
      }
      .form-input:disabled {
        background: var(--input-bg-disabled);
        color: var(--text-muted);
        cursor: not-allowed;
      }
      .maps-hint {
        display: inline-flex;
        align-items: center;
        gap: var(--space-2);
        font-size: var(--text-xs);
        color: var(--text-muted);
      }
      .maps-hint--error {
        color: var(--accent-error);
      }
    `,
  ],
})
export class AddressAutocompleteComponent implements ControlValueAccessor, AfterViewInit {
  private readonly mapsLoader = inject(GoogleMapsLoaderService);
  private readonly destroyRef = inject(DestroyRef);

  // ── Inputs / Outputs ──────────────────────
  readonly inputId = input<string>('address');
  readonly placeholder = input<string>('Start typing an address…');
  /** Whether the control value holds the street line or the full address. */
  readonly valueMode = input<AddressValueMode>('street');
  /** Optional ISO country code(s) to bias/restrict results (e.g. 'us'). */
  readonly country = input<string | string[] | undefined>(undefined);
  readonly addressSelected = output<AddressSelection>();

  private readonly addressInputRef =
    viewChild.required<ElementRef<HTMLInputElement>>('addressInput');

  // ── State ─────────────────────────────────
  protected readonly value = signal<string>('');
  protected readonly disabled = signal(false);
  protected readonly mapsLoading = signal(false);
  protected readonly mapsError = signal<string | null>(null);

  private onChange: (value: string) => void = () => {};
  protected onTouched: () => void = () => {};

  private autocomplete: google.maps.places.Autocomplete | null = null;
  private placeListener: google.maps.MapsEventListener | null = null;

  ngAfterViewInit(): void {
    void this.initAutocomplete();
    this.destroyRef.onDestroy(() => this.detach());
  }

  // ── ControlValueAccessor ──────────────────
  writeValue(value: unknown): void {
    this.value.set(value == null ? '' : String(value));
  }
  registerOnChange(fn: (value: string) => void): void {
    this.onChange = fn;
  }
  registerOnTouched(fn: () => void): void {
    this.onTouched = fn;
  }
  setDisabledState(isDisabled: boolean): void {
    this.disabled.set(isDisabled);
  }

  protected onInput(event: Event): void {
    const next = (event.target as HTMLInputElement).value;
    this.value.set(next);
    this.onChange(next);
  }

  // ── Google Maps wiring ────────────────────
  private async initAutocomplete(): Promise<void> {
    this.mapsLoading.set(true);
    this.mapsError.set(null);

    const loaded = await this.mapsLoader.load();
    this.mapsLoading.set(false);

    if (!loaded) {
      this.mapsError.set('Address autocomplete unavailable — you can type the address manually.');
      return;
    }

    const input = this.addressInputRef().nativeElement;
    const restrictions = this.country();

    this.autocomplete = new google.maps.places.Autocomplete(input, {
      types: ['address'],
      fields: ['name', 'formatted_address', 'address_components', 'geometry'],
      ...(restrictions ? { componentRestrictions: { country: restrictions } } : {}),
    });

    this.placeListener = this.autocomplete.addListener('place_changed', () => {
      const place = this.autocomplete?.getPlace();
      if (place) this.emitSelection(place, input.value);
    });
  }

  private emitSelection(place: google.maps.places.PlaceResult, fallback: string): void {
    const components = place.address_components ?? [];
    const get = (type: string, useShort = false): string => {
      const match = components.find((c) => c.types.includes(type));
      if (!match) return '';
      return useShort ? match.short_name : match.long_name;
    };

    const streetNumber = get('street_number');
    const route = get('route');
    const street = [streetNumber, route].filter(Boolean).join(' ').trim();
    const formatted = place.formatted_address ?? '';
    const address =
      this.valueMode() === 'full'
        ? formatted || street || place.name || fallback
        : street || place.name || formatted || fallback;

    const city =
      get('locality') ||
      get('postal_town') ||
      get('sublocality') ||
      get('administrative_area_level_2');
    const state = get('administrative_area_level_1', true);
    const zipcode = get('postal_code');
    const country = get('country');

    const location = place.geometry?.location;
    const latitude = location ? location.lat() : null;
    const longitude = location ? location.lng() : null;

    // Keep our own (CVA) value in sync with the chosen street line.
    this.value.set(address);
    this.onChange(address);
    this.onTouched();

    this.addressSelected.emit({
      address,
      city,
      state,
      zipcode,
      country,
      latitude,
      longitude,
      formattedAddress: formatted || address,
    });
  }

  private detach(): void {
    if (this.placeListener) {
      google.maps.event.removeListener(this.placeListener);
      this.placeListener = null;
    }
    if (this.autocomplete) {
      const input = this.addressInputRef()?.nativeElement;
      if (input) google.maps.event.clearInstanceListeners(input);
      this.autocomplete = null;
    }
  }
}
