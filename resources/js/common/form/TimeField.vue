<script setup lang="ts">
/**
 * Labelled 24-hour time picker. Composes FormField + the Volt DatePicker
 * primitive in `timeOnly` mode. The public `v-model` is a `HH:mm` string (what
 * the backend `date_format:H:i` rules expect); conversion to/from the picker's
 * native `Date` is handled internally so callers never touch `Date` objects.
 *
 * Layer: common/ — imports volt/ only, never modules/ or Pages/.
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
}>();

function pad(value: number): string {
    return String(value).padStart(2, '0');
}

function toHm(date: Date): string {
    return `${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

/** Anchors the chosen time to today's date; only H:i is ever read back out. */
function fromHm(value: string): Date {
    const [hours, minutes] = value.split(':');
    const date = new Date();
    date.setHours(Number(hours) || 0, Number(minutes) || 0, 0, 0);
    return date;
}

const timeValue = computed<Date | null>({
    get: () => (model.value ? fromHm(model.value) : null),
    set: (date) => {
        model.value = date ? toHm(date) : null;
    },
});
</script>

<template>
    <FormField :label="label" :for-id="name" :required="required" :error="error" :hint="hint">
        <DatePicker
            :input-id="name"
            v-model="timeValue"
            time-only
            hour-format="24"
            :placeholder="placeholder"
            :disabled="disabled"
            :invalid="!!error"
            fluid
        />
    </FormField>
</template>
