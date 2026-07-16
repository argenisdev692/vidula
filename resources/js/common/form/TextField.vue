<script setup lang="ts">
/**
 * Labelled text input. Composes FormField + the Volt InputText primitive.
 * `v-model` binds a string; supports any native input `type` (text, email,
 * password, tel, …). Server/client validation messages surface via `error`.
 */
import { computed } from 'vue';
import InputText from '@/volt/InputText.vue';
import FormField from './FormField.vue';

type HtmlInputMode = 'search' | 'text' | 'url' | 'email' | 'none' | 'tel' | 'numeric' | 'decimal';

const model = defineModel<string>({ default: '' });

const props = withDefaults(
    defineProps<{
        label?: string;
        /** Field name — also the input `id` for label association. */
        name?: string;
        error?: string;
        /** Positive (green) message + border — e.g. a passed availability check. */
        success?: string;
        hint?: string;
        required?: boolean;
        placeholder?: string;
        disabled?: boolean;
        type?: string;
        autocomplete?: string;
        maxlength?: number;
        inputmode?: HtmlInputMode;
    }>(),
    { type: 'text' },
);

/** Green border only when a success message is present and there is no error. */
const validClass = computed<string | undefined>(() =>
    props.success && !props.error ? 'border-[var(--accent-success)]! enabled:hover:border-[var(--accent-success)]!' : undefined,
);
</script>

<template>
    <FormField :label="label" :for-id="name" :required="required" :error="error" :success="success" :hint="hint">
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
            :class="validClass"
            fluid
        />
    </FormField>
</template>
