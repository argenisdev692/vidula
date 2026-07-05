<script setup lang="ts">
/**
 * Labelled text input. Composes FormField + the Volt InputText primitive.
 * `v-model` binds a string; supports any native input `type` (text, email,
 * password, tel, …). Server/client validation messages surface via `error`.
 */
import InputText from '@/volt/InputText.vue';
import FormField from './FormField.vue';

const model = defineModel<string>({ default: '' });

withDefaults(
    defineProps<{
        label?: string;
        /** Field name — also the input `id` for label association. */
        name?: string;
        error?: string;
        hint?: string;
        required?: boolean;
        placeholder?: string;
        disabled?: boolean;
        type?: string;
        autocomplete?: string;
        maxlength?: number;
        inputmode?: string;
    }>(),
    { type: 'text' },
);
</script>

<template>
    <FormField :label="label" :for-id="name" :required="required" :error="error" :hint="hint">
        <InputText
            :id="name"
            v-model="model"
            :type="type"
            :placeholder="placeholder"
            :disabled="disabled"
            :autocomplete="autocomplete"
            :maxlength="maxlength"
            :inputmode="inputmode"
            :invalid="!!error"
            fluid
        />
    </FormField>
</template>
