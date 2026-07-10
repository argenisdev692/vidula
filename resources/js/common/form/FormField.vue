<script setup lang="ts">
/**
 * Domain-agnostic form-field layout primitive. Renders a label (with optional
 * required marker), a control via the default slot, and either an error message
 * or a hint below it. Every typed field in this folder composes it, so labels,
 * spacing, required markers and error styling stay consistent across modules.
 * Styling is token-driven (globals.css) — no hardcoded colors.
 */
defineProps<{
    label?: string;
    /** `id`/`for` linking the label to its control. */
    forId?: string;
    required?: boolean;
    error?: string;
    /** Positive (green) message — e.g. a passed realtime availability check. */
    success?: string;
    hint?: string;
}>();
</script>

<template>
    <div class="form-field">
        <label v-if="label" :for="forId" class="form-field__label">
            {{ label }}
            <span v-if="required" class="form-field__required" aria-hidden="true">*</span>
        </label>

        <slot />

        <p v-if="error" class="form-field__error" role="alert">{{ error }}</p>
        <p v-else-if="success" class="form-field__success">{{ success }}</p>
        <p v-else-if="hint" class="form-field__hint">{{ hint }}</p>
    </div>
</template>

<style scoped>
.form-field {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
    min-width: 0;
}

.form-field__label {
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
    color: var(--text-secondary);
}

.form-field__required {
    color: var(--accent-error);
    margin-left: 2px;
}

.form-field__error {
    margin: 0;
    font-size: var(--text-xs);
    color: var(--accent-error);
}

.form-field__success {
    margin: 0;
    font-size: var(--text-xs);
    color: var(--accent-success);
}

.form-field__hint {
    margin: 0;
    font-size: var(--text-xs);
    color: var(--text-muted);
}
</style>
