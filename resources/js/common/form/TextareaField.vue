<script setup lang="ts">
/**
 * Labelled multi-line text input. Composes FormField + the Volt Textarea
 * primitive. `v-model` binds a string; `rows` sets the initial height and
 * `autoResize` grows the control with its content.
 */
import Textarea from '@/volt/Textarea.vue';
import FormField from './FormField.vue';

const model = defineModel<string>({ default: '' });

withDefaults(
    defineProps<{
        label?: string;
        name?: string;
        error?: string;
        hint?: string;
        required?: boolean;
        placeholder?: string;
        disabled?: boolean;
        rows?: number;
        autoResize?: boolean;
        maxlength?: number;
    }>(),
    { rows: 4, autoResize: true },
);
</script>

<template>
    <FormField :label="label" :for-id="name" :required="required" :error="error" :hint="hint">
        <Textarea
            :id="name"
            v-model="model"
            :rows="rows"
            :auto-resize="autoResize"
            :placeholder="placeholder"
            :disabled="disabled"
            :maxlength="maxlength"
            :invalid="!!error"
            fluid
        />
    </FormField>
</template>
