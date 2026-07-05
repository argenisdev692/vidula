<script setup lang="ts">
/**
 * Form submit button with a built-in loading state. Composes the Volt Button
 * primitive; while `loading` is true it shows a spinner and is disabled so a
 * form cannot be submitted twice. When `disabled` it stays VISIBLE but greyed
 * out (never hidden) — callers control submittability via `:disabled`. Ports
 * the GUIDE `form-submit-button`.
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
    { type: 'submit', iconPos: 'left', disabled: false },
);
</script>

<template>
    <Button
        class="form-submit-btn"
        :type="type"
        :label="label"
        :icon="loading ? undefined : icon"
        :icon-pos="iconPos"
        :loading="loading"
        :disabled="disabled || loading"
    />
</template>

<style scoped>
/* Login submit gradient (cyan → blue) + hover lift — no shine sweep (::before). */
.form-submit-btn {
    padding: var(--space-4) var(--space-8);
    font-family: var(--font-sans);
    font-size: var(--text-base);
    font-weight: var(--font-semibold);
    background: linear-gradient(120deg, var(--blue-500) 0%, var(--blue) 60%, var(--cyan) 100%) !important;
    color: var(--on-accent) !important;
    border: none !important;
    border-radius: var(--radius-lg) !important;
    box-shadow: 0 4px 20px color-mix(in srgb, var(--accent-primary) 35%, transparent);
    transition: transform var(--transition), box-shadow var(--transition);
}

.form-submit-btn:hover:not(:disabled):not(.p-disabled) {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px color-mix(in srgb, var(--accent-primary) 50%, transparent);
    background: linear-gradient(120deg, var(--blue-500) 0%, var(--blue) 60%, var(--cyan) 100%) !important;
}

.form-submit-btn:active:not(:disabled):not(.p-disabled) {
    transform: translateY(0);
}

.form-submit-btn:disabled,
.form-submit-btn.p-disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>
