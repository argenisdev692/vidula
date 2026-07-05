<script setup lang="ts">
/**
 * Labelled single-select. Composes FormField + the Volt Select primitive.
 * `options` is a `{ label, value }` list; `v-model` binds the chosen value.
 * `filter` turns the dropdown into a searchable one; `showClear` adds a reset.
 */
import Select from '@/volt/Select.vue';
import FormField from './FormField.vue';
import type { SelectOption } from './types';

const model = defineModel<string | null>({ default: null });

defineProps<{
    label?: string;
    name?: string;
    error?: string;
    hint?: string;
    required?: boolean;
    placeholder?: string;
    disabled?: boolean;
    options: SelectOption[];
    filter?: boolean;
    showClear?: boolean;
}>();
</script>

<template>
    <FormField :label="label" :for-id="name" :required="required" :error="error" :hint="hint">
        <Select
            :input-id="name"
            v-model="model"
            :options="options"
            option-label="label"
            option-value="value"
            :placeholder="placeholder"
            :disabled="disabled"
            :filter="filter"
            :show-clear="showClear"
            :invalid="!!error"
            fluid
        />
    </FormField>
</template>
