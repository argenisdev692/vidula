<script setup lang="ts">
/**
 * Universal phone input. A country selector (flag + dial code, searchable) sits
 * beside a national-number input that formats as you type. `v-model` is a single
 * **E.164** string (e.g. `+14155552671`) — the canonical storage format across
 * every module — or `null` when empty.
 *
 * Powered by libphonenumber-js: it parses the model back into country + national
 * parts on hydration, formats keystrokes (`AsYouType`), and composes the E.164
 * value. Final validity is enforced by the consumer's Zod schema / backend.
 */
import { AsYouType, getCountryCallingCode, parsePhoneNumberFromString } from 'libphonenumber-js';
import type { CountryCode } from 'libphonenumber-js';
import { computed, nextTick, ref, watch } from 'vue';
import InputText from '@/volt/InputText.vue';
import Select from '@/volt/Select.vue';
import FormField from './FormField.vue';
import { buildPhoneCountries } from './phoneCountries';

const model = defineModel<string | null>({ default: null });

const props = withDefaults(
    defineProps<{
        label?: string;
        /** Field name — also the national input `id` for label association. */
        name?: string;
        error?: string;
        hint?: string;
        required?: boolean;
        placeholder?: string;
        disabled?: boolean;
        /** ISO country pre-selected when the model is empty. */
        defaultCountry?: CountryCode;
    }>(),
    { defaultCountry: 'US', placeholder: 'Phone number' },
);

const countries = buildPhoneCountries();

const country = ref<CountryCode>(props.defaultCountry);
const national = ref('');

const selectedCountry = computed(() => countries.find((item) => item.code === country.value));

/** True while we are writing the model ourselves, to skip our own watcher. */
let syncing = false;

/** Rebuild country + national display from an incoming E.164 value. */
function hydrate(value: string | null): void {
    if (!value) {
        national.value = '';
        return;
    }

    const parsed = parsePhoneNumberFromString(value);
    if (parsed) {
        country.value = parsed.country ?? country.value;
        national.value = parsed.formatNational();
    } else {
        national.value = value;
    }
}

watch(model, (value) => {
    if (!syncing) {
        hydrate(value);
    }
}, { immediate: true });

/** Compose the current country + national parts into an E.164 model value. */
function emitModel(): void {
    const parsed = parsePhoneNumberFromString(national.value, country.value);
    const digits = national.value.replace(/\D/g, '');
    const next = parsed ? parsed.number : digits ? `+${getCountryCallingCode(country.value)}${digits}` : null;

    syncing = true;
    model.value = next;
    void nextTick(() => {
        syncing = false;
    });
}

function onNationalInput(value: unknown): void {
    national.value = new AsYouType(country.value).input(typeof value === 'string' ? value : '');
    emitModel();
}

function onCountryChange(): void {
    // Reformat the already-typed digits under the newly selected country.
    national.value = new AsYouType(country.value).input(national.value);
    emitModel();
}
</script>

<template>
    <FormField :label="label" :for-id="name" :required="required" :error="error" :hint="hint">
        <div class="phone">
            <Select
                v-model="country"
                :options="countries"
                option-label="label"
                option-value="code"
                :disabled="disabled"
                :invalid="!!error"
                filter
                :filter-placeholder="'Search country'"
                :aria-label="'Country calling code'"
                class="phone__country"
                @change="onCountryChange"
            >
                <template #value>
                    <span v-if="selectedCountry" class="phone__value">
                        <span class="phone__flag">{{ selectedCountry.flag }}</span>
                        <span class="phone__code">+{{ selectedCountry.callingCode }}</span>
                    </span>
                </template>
                <template #option="{ option }">
                    <span class="phone__option">
                        <span class="phone__flag">{{ option.flag }}</span>
                        <span class="phone__option-name">{{ option.name }}</span>
                        <span class="phone__option-code">+{{ option.callingCode }}</span>
                    </span>
                </template>
            </Select>

            <InputText
                :id="name"
                :model-value="national"
                type="tel"
                inputmode="tel"
                autocomplete="tel-national"
                :placeholder="placeholder"
                :disabled="disabled"
                :invalid="!!error"
                class="phone__national"
                fluid
                @update:model-value="onNationalInput"
            />
        </div>
    </FormField>
</template>

<style scoped>
.phone {
    display: flex;
    align-items: stretch;
    gap: var(--space-2);
}

.phone__country {
    flex: 0 0 auto;
    min-width: 6.5rem;
}

.phone__national {
    flex: 1 1 auto;
    min-width: 0;
}

.phone__value,
.phone__option {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    min-width: 0;
}

.phone__flag {
    font-size: var(--text-lg);
    line-height: 1;
}

.phone__code {
    font-weight: var(--font-medium);
    color: var(--text-primary);
}

.phone__option-name {
    flex: 1 1 auto;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.phone__option-code {
    color: var(--text-muted);
    font-variant-numeric: tabular-nums;
}
</style>
