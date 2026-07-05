<script setup lang="ts">
/**
 * Form submit button with a built-in loading state. Composes the Volt Button
 * primitive; while `loading` is true it shows a spinner and is disabled so a
 * form cannot be submitted twice. Ports the GUIDE `form-submit-button`.
 */
import Button from '@/volt/Button.vue';

withDefaults(
    defineProps<{
        label: string;
        /** primeicons class, e.g. `pi pi-check`. */
        icon?: string;
        loading?: boolean;
        disabled?: boolean;
        /** `submit` triggers the enclosing <form>; `button` for standalone use. */
        type?: 'submit' | 'button';
        iconPos?: 'left' | 'right';
    }>(),
    { type: 'submit', iconPos: 'left' },
);
</script>

<template>
    <Button
        :type="type"
        :label="label"
        :icon="loading ? undefined : icon"
        :icon-pos="iconPos"
        :loading="loading"
        :disabled="disabled || loading"
    />
</template>
