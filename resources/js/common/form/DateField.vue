<script setup lang="ts">
/**
 * Labelled date picker. Composes FormField + the Volt DatePicker primitive.
 * The public `v-model` is an ISO `YYYY-MM-DD` string (what the backend expects);
 * conversion to/from the picker's native `Date` is handled internally so callers
 * never deal with `Date` objects or timezone drift.
 */
import { computed } from 'vue';
import DatePicker from '@/volt/DatePicker.vue';
import FormField from './FormField.vue';

const model = defineModel<string | null>({ default: null });

defineProps<{
    label?: string;
    name?: string;
    error?: string;
    hint?: string;
    required?: boolean;
    placeholder?: string;
    disabled?: boolean;
    /** Earliest selectable date, as `YYYY-MM-DD` (past dates are disabled in the picker). */
    minDate?: string;
    /** Latest selectable date, as `YYYY-MM-DD`. */
    maxDate?: string;
}>();

function toIso(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

/** Local-noon parse avoids the UTC-midnight-to-previous-day rollover. */
function fromIso(value: string): Date {
    return new Date(`${value}T12:00:00`);
}

const dateValue = computed<Date | null>({
    get: () => (model.value ? fromIso(model.value) : null),
    set: (date) => {
        model.value = date ? toIso(date) : null;
    },
});
</script>

<template>
    <FormField :label="label" :for-id="name" :required="required" :error="error" :hint="hint">
        <DatePicker
            :input-id="name"
            v-model="dateValue"
            date-format="yy-mm-dd"
            :placeholder="placeholder"
            :disabled="disabled"
            :min-date="minDate ? fromIso(minDate) : undefined"
            :max-date="maxDate ? fromIso(maxDate) : undefined"
            :invalid="!!error"
            show-icon
            icon-display="input"
            fluid
        />
    </FormField>
</template>
