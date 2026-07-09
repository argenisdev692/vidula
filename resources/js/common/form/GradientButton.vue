<script setup lang="ts">
/**
 * Primary call-to-action button — a blue→cyan gradient with a sweeping shine,
 * the single "cyan animated" action identity used across the app: the list
 * toolbar's New action, modal confirm buttons, and form submits.
 *
 * Tones: `primary` (blue→cyan + shine), `danger` / `success` (solid accent
 * gradient, no shine) for destructive / restorative confirmations. Composes a
 * native <button> (like the GUIDE animated-button) rather than the Volt Button
 * so the shine overlay + loading spinner are fully controllable.
 *
 * Layer: common/ — imports nothing but Vue; safe for common/ + Pages/ callers.
 */
import { ref } from 'vue';

withDefaults(
    defineProps<{
        label?: string;
        /** primeicons class, e.g. `pi pi-check`. */
        icon?: string;
        tone?: 'primary' | 'danger' | 'success';
        type?: 'button' | 'submit';
        loading?: boolean;
        disabled?: boolean;
        /** Stretch to the full width of the parent. */
        block?: boolean;
        /** Animated shine sweep — only rendered for the `primary` tone. */
        shine?: boolean;
    }>(),
    {
        label: '',
        icon: '',
        tone: 'primary',
        type: 'button',
        loading: false,
        disabled: false,
        block: false,
        shine: true,
    },
);

defineEmits<{ click: [] }>();

const el = ref<HTMLButtonElement | null>(null);

/** Let a parent modal move focus onto the confirm action for accessibility. */
defineExpose({ focus: () => el.value?.focus() });
</script>

<template>
    <button
        ref="el"
        :type="type"
        class="grad-btn"
        :class="[`grad-btn--${tone}`, { 'grad-btn--block': block }]"
        :disabled="disabled || loading"
        @click="$emit('click')"
    >
        <span v-if="shine && tone === 'primary'" class="grad-btn__shine" aria-hidden="true" />
        <span class="grad-btn__content">
            <i v-if="loading" class="pi pi-spinner pi-spin grad-btn__icon" aria-hidden="true" />
            <i v-else-if="icon" class="grad-btn__icon" :class="icon" aria-hidden="true" />
            <span v-if="label">{{ label }}</span>
        </span>
    </button>
</template>

<style scoped>
.grad-btn {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 2.5rem;
    padding: var(--space-2) var(--space-5);
    font-family: var(--font-sans);
    font-size: var(--text-sm);
    font-weight: var(--font-semibold);
    color: var(--on-accent);
    border: none;
    border-radius: var(--radius-lg);
    cursor: pointer;
    overflow: hidden;
    transition: transform var(--transition), box-shadow var(--transition), opacity var(--transition);
}

.grad-btn--block {
    width: 100%;
}

.grad-btn--primary {
    background: linear-gradient(120deg, var(--blue-500) 0%, var(--blue) 55%, var(--cyan) 100%);
    box-shadow: 0 4px 18px color-mix(in srgb, var(--accent-primary) 35%, transparent);
}

.grad-btn--danger {
    background: linear-gradient(135deg, var(--accent-error) 0%, color-mix(in srgb, var(--accent-error) 72%, var(--bg-void)) 100%);
    box-shadow: 0 4px 18px color-mix(in srgb, var(--accent-error) 35%, transparent);
}

.grad-btn--success {
    background: linear-gradient(135deg, var(--accent-success) 0%, color-mix(in srgb, var(--accent-success) 72%, var(--bg-void)) 100%);
    box-shadow: 0 4px 18px color-mix(in srgb, var(--accent-success) 35%, transparent);
}

.grad-btn:hover:not(:disabled) {
    transform: translateY(-2px);
}

.grad-btn--primary:hover:not(:disabled) {
    box-shadow: 0 8px 26px color-mix(in srgb, var(--accent-primary) 48%, transparent);
}

.grad-btn--danger:hover:not(:disabled) {
    box-shadow: 0 8px 26px color-mix(in srgb, var(--accent-error) 48%, transparent);
}

.grad-btn--success:hover:not(:disabled) {
    box-shadow: 0 8px 26px color-mix(in srgb, var(--accent-success) 48%, transparent);
}

.grad-btn:active:not(:disabled) {
    transform: translateY(0);
}

.grad-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.grad-btn__content {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    z-index: 1;
}

.grad-btn__icon {
    font-size: var(--text-base);
    line-height: 1;
}

.grad-btn__shine {
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.28) 50%, transparent 70%);
    animation: grad-btn-shine 3s ease infinite;
    pointer-events: none;
}

@keyframes grad-btn-shine {
    0% {
        transform: translateX(-100%) rotate(45deg);
    }
    100% {
        transform: translateX(200%) rotate(45deg);
    }
}

@media (prefers-reduced-motion: reduce) {
    .grad-btn__shine {
        animation: none;
    }

    .grad-btn:hover:not(:disabled) {
        transform: none;
    }
}
</style>
