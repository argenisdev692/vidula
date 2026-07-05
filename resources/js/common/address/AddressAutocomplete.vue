<script setup lang="ts">
/**
 * Reusable address field group with Google Maps (Places New) autocomplete.
 *
 * - `address` is the only lookup input: typing queries Google; picking a
 *   suggestion fills the rest and captures latitude/longitude silently.
 * - `address_2` is always manual (apartment, suite, …).
 * - `zip_code`, `city`, `state`, `country` are read-only by default. When Google
 *   omits one after a selection the field is flagged "not found" and the user
 *   MAY unlock it to type manually — never forced (lock toggle per field).
 *
 * Domain-agnostic: bind a single `AddressValue` via `v-model` and pass the
 * Inertia-shared Maps key as `api-key`. No Google widget is injected, so the UI
 * stays fully token-styled and WCAG-AA (ARIA combobox pattern).
 */
import FormField from '@/common/form/FormField.vue';
import TextField from '@/common/form/TextField.vue';
import InputText from '@/volt/InputText.vue';
import { onClickOutside, useDebounceFn } from '@vueuse/core';
import { computed, reactive, ref, useId, useTemplateRef } from 'vue';
import type { AddressErrors, AddressValue, ManagedAddressField } from './types';
import { type AddressSuggestion, usePlacesAutocomplete } from './usePlacesAutocomplete';

const props = withDefaults(
    defineProps<{
        /** Google Maps JS API key — from `usePage().props.config.google_maps_api_key`. */
        apiKey: string;
        errors?: AddressErrors;
        disabled?: boolean;
        /** Bias predictions to these ISO-3166 region codes (e.g. `['US']`). */
        regionCodes?: string[];
    }>(),
    { errors: () => ({}), disabled: false, regionCodes: () => [] },
);

const model = defineModel<AddressValue>({ required: true });

const { suggestions, loading, failed, search, resolve, clear } = usePlacesAutocomplete(props.apiKey);

/** Immutable single-field update so `v-model` reactivity always fires. */
function patch(changes: Partial<AddressValue>): void {
    model.value = { ...model.value, ...changes };
}

/* -------------------------------------------------------------------------- */
/* Autocomplete input + suggestion listbox                                    */
/* -------------------------------------------------------------------------- */

const listboxId = useId();
const open = ref(false);
const activeIndex = ref(-1);
const hasSelectedPlace = ref(false);
const root = useTemplateRef<HTMLElement>('root');

onClickOutside(root, () => {
    open.value = false;
});

const runSearch = useDebounceFn(async (value: string) => {
    await search(value);
    open.value = suggestions.value.length > 0 || failed.value;
    activeIndex.value = -1;
}, 250);

const addressInput = computed<string>({
    get: () => model.value.address ?? '',
    set: (value) => {
        patch({ address: value || null });
        void runSearch(value);
    },
});

/** Always-manual line 2 — bridges the nullable model to TextField's string. */
const addressLine2 = computed<string>({
    get: () => model.value.address_2 ?? '',
    set: (value) => patch({ address_2: value || null }),
});

async function selectSuggestion(suggestion: AddressSuggestion): Promise<void> {
    const resolved = await resolve(suggestion);
    // Keep the street line Google gave us; fall back to the prediction label.
    patch({ ...resolved, address: resolved.address ?? suggestion.primary });
    hasSelectedPlace.value = true;
    unlocked.clear();
    open.value = false;
    activeIndex.value = -1;
    clear();
}

function onKeydown(event: KeyboardEvent): void {
    if (!open.value) {
        return;
    }

    switch (event.key) {
        case 'ArrowDown':
            event.preventDefault();
            activeIndex.value = (activeIndex.value + 1) % suggestions.value.length;
            break;
        case 'ArrowUp':
            event.preventDefault();
            activeIndex.value = activeIndex.value <= 0 ? suggestions.value.length - 1 : activeIndex.value - 1;
            break;
        case 'Enter':
            if (activeIndex.value >= 0) {
                event.preventDefault();
                void selectSuggestion(suggestions.value[activeIndex.value]);
            }
            break;
        case 'Escape':
            open.value = false;
            activeIndex.value = -1;
            break;
    }
}

/* -------------------------------------------------------------------------- */
/* Read-only managed fields + per-field manual unlock                         */
/* -------------------------------------------------------------------------- */

const managedFields: ReadonlyArray<{
    key: ManagedAddressField;
    label: string;
    autocomplete: string;
    placeholder: string;
}> = [
    { key: 'city', label: 'City', autocomplete: 'address-level2', placeholder: 'City' },
    { key: 'state', label: 'State / Province', autocomplete: 'address-level1', placeholder: 'State' },
    { key: 'zip_code', label: 'ZIP / Postal code', autocomplete: 'postal-code', placeholder: 'ZIP' },
    { key: 'country', label: 'Country', autocomplete: 'country-name', placeholder: 'Country' },
];

const unlocked = reactive(new Set<ManagedAddressField>());

/** Write a managed field back to the model, normalising empty string to null. */
function updateManaged(field: ManagedAddressField, value: unknown): void {
    model.value = { ...model.value, [field]: typeof value === 'string' && value.length > 0 ? value : null };
}

function toggleLock(field: ManagedAddressField): void {
    if (unlocked.has(field)) {
        unlocked.delete(field);
    } else {
        unlocked.add(field);
    }
}

/** A place was chosen but Google returned nothing for this field. */
function isMissing(field: ManagedAddressField): boolean {
    return hasSelectedPlace.value && !model.value[field] && !unlocked.has(field);
}
</script>

<template>
    <div class="address">
        <!-- Autocomplete lookup -->
        <div ref="root" class="address__lookup">
            <FormField
                label="Address"
                :for-id="listboxId + '-input'"
                :error="errors.address"
                hint="Start typing to search — we’ll fill in the rest."
            >
                <div class="address__combobox">
                    <span class="address__icon" aria-hidden="true"><i class="pi pi-map-marker" /></span>
                    <InputText
                        :id="listboxId + '-input'"
                        v-model="addressInput"
                        role="combobox"
                        aria-autocomplete="list"
                        :aria-expanded="open"
                        :aria-controls="listboxId"
                        :aria-activedescendant="activeIndex >= 0 ? `${listboxId}-opt-${activeIndex}` : undefined"
                        autocomplete="off"
                        placeholder="Search for an address"
                        :disabled="disabled"
                        :invalid="!!errors.address"
                        class="address__input"
                        fluid
                        @keydown="onKeydown"
                        @focus="open = suggestions.length > 0"
                    />
                    <span v-if="loading" class="address__spinner" aria-hidden="true">
                        <i class="pi pi-spin pi-spinner" />
                    </span>
                </div>
            </FormField>

            <ul v-if="open" :id="listboxId" class="address__listbox" role="listbox" aria-label="Address suggestions">
                <li
                    v-for="(suggestion, index) in suggestions"
                    :id="`${listboxId}-opt-${index}`"
                    :key="suggestion.id"
                    class="address__option"
                    :class="{ 'address__option--active': index === activeIndex }"
                    role="option"
                    :aria-selected="index === activeIndex"
                    @mouseenter="activeIndex = index"
                    @mousedown.prevent="selectSuggestion(suggestion)"
                >
                    <i class="pi pi-map-marker address__option-icon" aria-hidden="true" />
                    <span class="address__option-text">
                        <span class="address__option-primary">{{ suggestion.primary }}</span>
                        <span v-if="suggestion.secondary" class="address__option-secondary">
                            {{ suggestion.secondary }}
                        </span>
                    </span>
                </li>

                <li v-if="!suggestions.length && failed" class="address__empty" role="presentation">
                    Address lookup is unavailable right now — you can fill the fields below manually.
                </li>
                <li v-else-if="!suggestions.length && !loading" class="address__empty" role="presentation">
                    No matches found.
                </li>

                <li class="address__attribution" role="presentation" aria-hidden="true">Powered by Google</li>
            </ul>
        </div>

        <!-- Always-manual line 2 -->
        <TextField
            v-model="addressLine2"
            name="address_2"
            label="Address 2"
            placeholder="Apartment, suite, unit, etc. (optional)"
            :disabled="disabled"
            :error="errors.address_2"
        />

        <!-- Read-only managed fields with opt-in manual unlock -->
        <div class="address__grid">
            <FormField
                v-for="field in managedFields"
                :key="field.key"
                :label="field.label"
                :for-id="`address-${field.key}`"
                :error="errors[field.key]"
                :hint="isMissing(field.key) ? 'Not found — unlock to add it manually.' : undefined"
            >
                <div class="address__managed" :class="{ 'address__managed--missing': isMissing(field.key) }">
                    <InputText
                        :id="`address-${field.key}`"
                        :model-value="model[field.key] ?? ''"
                        :readonly="!unlocked.has(field.key)"
                        :disabled="disabled"
                        :autocomplete="field.autocomplete"
                        :placeholder="field.placeholder"
                        :invalid="!!errors[field.key]"
                        class="address__managed-input"
                        fluid
                        @update:model-value="updateManaged(field.key, $event)"
                    />
                    <button
                        type="button"
                        class="address__lock"
                        :disabled="disabled"
                        :aria-pressed="unlocked.has(field.key)"
                        :aria-label="
                            unlocked.has(field.key)
                                ? `Lock ${field.label} field`
                                : `Unlock ${field.label} field to edit manually`
                        "
                        @click="toggleLock(field.key)"
                    >
                        <i :class="unlocked.has(field.key) ? 'pi pi-lock-open' : 'pi pi-lock'" aria-hidden="true" />
                    </button>
                </div>
            </FormField>
        </div>
    </div>
</template>

<style scoped>
.address {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
}

.address__lookup {
    position: relative;
}

.address__combobox {
    position: relative;
}

.address__icon {
    position: absolute;
    top: 50%;
    left: var(--space-3);
    transform: translateY(-50%);
    color: var(--text-muted);
    pointer-events: none;
    z-index: 1;
}

.address__input :deep(input),
.address__input {
    padding-left: var(--space-8);
}

.address__spinner {
    position: absolute;
    top: 50%;
    right: var(--space-3);
    transform: translateY(-50%);
    color: var(--text-muted);
}

.address__listbox {
    position: absolute;
    z-index: 20;
    left: 0;
    right: 0;
    margin: var(--space-1) 0 0;
    padding: var(--space-1);
    list-style: none;
    background: var(--bg-surface);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-md);
    box-shadow: 0 6px 18px color-mix(in srgb, var(--text-primary) 14%, transparent);
    max-height: 18rem;
    overflow-y: auto;
}

.address__option {
    display: flex;
    align-items: flex-start;
    gap: var(--space-2);
    padding: var(--space-2) var(--space-3);
    border-radius: var(--radius-sm);
    cursor: pointer;
    color: var(--text-primary);
}

.address__option--active {
    background: var(--bg-hover);
}

.address__option-icon {
    margin-top: 2px;
    color: var(--text-muted);
    font-size: var(--text-sm);
}

.address__option-text {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.address__option-primary {
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
}

.address__option-secondary {
    font-size: var(--text-xs);
    color: var(--text-muted);
}

.address__empty {
    padding: var(--space-2) var(--space-3);
    font-size: var(--text-sm);
    color: var(--text-muted);
}

.address__attribution {
    padding: var(--space-1) var(--space-3);
    font-size: var(--text-xs);
    color: var(--text-disabled);
    text-align: right;
    border-top: 1px solid var(--border-subtle);
    margin-top: var(--space-1);
}

.address__grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: var(--space-4);
}

@media (min-width: 640px) {
    .address__grid {
        grid-template-columns: 1fr 1fr;
    }
}

.address__managed {
    position: relative;
}

.address__managed-input :deep(input),
.address__managed-input {
    padding-right: var(--space-10);
}

.address__lock {
    position: absolute;
    top: 50%;
    right: var(--space-2);
    transform: translateY(-50%);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.75rem;
    height: 1.75rem;
    border: none;
    border-radius: var(--radius-sm);
    background: transparent;
    color: var(--text-muted);
    cursor: pointer;
    transition: color 0.15s ease, background 0.15s ease;
}

.address__lock:hover:not(:disabled) {
    color: var(--text-primary);
    background: var(--bg-hover);
}

.address__lock:disabled {
    cursor: not-allowed;
    color: var(--text-disabled);
}

.address__managed--missing .address__lock {
    color: var(--accent-warning);
}
</style>
