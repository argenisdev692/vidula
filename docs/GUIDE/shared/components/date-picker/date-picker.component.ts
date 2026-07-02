import { Component, forwardRef, input, signal } from '@angular/core';
import {
  ControlValueAccessor,
  FormsModule,
  NG_VALUE_ACCESSOR,
} from '@angular/forms';
import { DatePickerModule } from 'primeng/datepicker';

/** How the DatePicker selects dates. Mirrors PrimeNG's `selectionMode`. */
export type DatePickerSelectionMode = 'single' | 'range' | 'multiple';

/**
 * Shape of the value exposed to the parent form control:
 * - `'iso'`  → `'YYYY-MM-DD'` string (single mode) — matches the app's string-based DTOs.
 * - `'date'` → native `Date` (single) / `Date[]` (range/multiple) — for filters.
 */
export type DatePickerValueFormat = 'iso' | 'date';

type InternalValue = Date | Date[] | null;

/**
 * Reusable date picker built on PrimeNG v21 `p-datepicker`, styled to match the
 * app's `.form-input` design via the global `.form-datepicker` class.
 *
 * It is a {@link ControlValueAccessor}, so it works with both Reactive Forms
 * (`formControlName`) and template forms (`[(ngModel)]`). Single-date forms keep
 * using `'YYYY-MM-DD'` strings (default `valueFormat="iso"`); date-range filters
 * use native `Date[]` (`valueFormat="date"`).
 */
@Component({
  selector: 'app-date-picker',
  imports: [FormsModule, DatePickerModule],
  providers: [
    {
      provide: NG_VALUE_ACCESSOR,
      useExisting: forwardRef(() => DatePickerComponent),
      multi: true,
    },
  ],
  template: `
    <p-datepicker
      [inputId]="inputId()"
      [ngModel]="internalValue()"
      (ngModelChange)="onModelChange($event)"
      [selectionMode]="selectionMode()"
      [dateFormat]="dateFormat()"
      [placeholder]="placeholder()"
      [readonlyInput]="readonlyInput()"
      [showButtonBar]="showButtonBar()"
      [showIcon]="showIcon()"
      iconDisplay="input"
      [minDate]="minDate()"
      [maxDate]="maxDate()"
      [disabled]="disabled()"
      [ariaLabel]="ariaLabel()"
      [fluid]="true"
      appendTo="body"
      styleClass="form-datepicker" />
  `,
})
export class DatePickerComponent implements ControlValueAccessor {
  // ── Inputs ────────────────────────────────
  readonly inputId = input<string>();
  readonly placeholder = input<string>('Select date');
  readonly selectionMode = input<DatePickerSelectionMode>('single');
  readonly dateFormat = input<string>('dd/mm/yy');
  readonly valueFormat = input<DatePickerValueFormat>('iso');
  readonly showButtonBar = input<boolean>(true);
  readonly showIcon = input<boolean>(true);
  readonly readonlyInput = input<boolean>(false);
  readonly minDate = input<Date | undefined>(undefined);
  readonly maxDate = input<Date | undefined>(undefined);
  readonly ariaLabel = input<string>();

  // ── CVA state ─────────────────────────────
  protected readonly internalValue = signal<InternalValue>(null);
  protected readonly disabled = signal(false);

  private onChange: (value: unknown) => void = () => {};
  private onTouched: () => void = () => {};

  /** Bridges the inner `p-datepicker` change back to the parent control. */
  onModelChange(value: InternalValue): void {
    this.internalValue.set(value);
    this.onChange(this.toOutput(value));
    this.onTouched();
  }

  // ── ControlValueAccessor ──────────────────
  writeValue(value: unknown): void {
    this.internalValue.set(this.toInternal(value));
  }

  registerOnChange(fn: (value: unknown) => void): void {
    this.onChange = fn;
  }

  registerOnTouched(fn: () => void): void {
    this.onTouched = fn;
  }

  setDisabledState(isDisabled: boolean): void {
    this.disabled.set(isDisabled);
  }

  // ── Value conversion ──────────────────────
  /** Parent value → native `Date`(s) the PrimeNG picker understands. */
  private toInternal(value: unknown): InternalValue {
    if (value === null || value === undefined || value === '') return null;
    if (this.valueFormat() === 'date') {
      return value as InternalValue;
    }
    return typeof value === 'string' ? this.parseIso(value) : (value as InternalValue);
  }

  /** Native `Date`(s) → the format the parent control expects. */
  private toOutput(value: InternalValue): unknown {
    if (this.valueFormat() === 'date') return value;
    if (value === null) return null;
    const single = Array.isArray(value) ? value[0] : value;
    return single ? this.formatIso(single) : null;
  }

  /** Parse `'YYYY-MM-DD'` as a local date (no timezone shift). */
  private parseIso(value: string): Date | null {
    const [year, month, day] = value.split('-').map(Number);
    if (!year || !month || !day) return null;
    return new Date(year, month - 1, day);
  }

  /** Format a local `Date` as `'YYYY-MM-DD'`. */
  private formatIso(date: Date): string {
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${date.getFullYear()}-${month}-${day}`;
  }
}
