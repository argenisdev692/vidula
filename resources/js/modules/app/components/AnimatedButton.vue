<script setup lang="ts">
/**
 * Gradient call-to-action with a sweeping shine, ported from the GUIDE Angular
 * `app-animated-button`. Used as the page-header primary action. Emits `click`;
 * the parent owns navigation/behaviour.
 */
withDefaults(
    defineProps<{
        text: string;
        icon?: string;
        variant?: 'primary' | 'secondary' | 'accent';
    }>(),
    { icon: '', variant: 'primary' },
);

defineEmits<{ click: [] }>();
</script>

<template>
    <button type="button" class="animated-button" :class="variant" @click="$emit('click')">
        <span class="button-shine" aria-hidden="true" />
        <span class="button-content">
            <i v-if="icon" class="button-icon pi" :class="icon" aria-hidden="true" />
            <span class="button-text">{{ text }}</span>
        </span>
    </button>
</template>

<style scoped>
.animated-button {
    position: relative;
    padding: var(--space-3) var(--space-8);
    font-family: var(--font-sans);
    font-size: var(--text-sm);
    font-weight: var(--font-semibold);
    border: none;
    border-radius: var(--radius-lg);
    cursor: pointer;
    overflow: hidden;
    transition: transform var(--transition-slow), box-shadow var(--transition-slow);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--on-accent);
}

.animated-button.primary {
    background: var(--grad-primary);
    box-shadow: 0 4px 15px color-mix(in srgb, var(--accent-primary) 40%, transparent);
}

.animated-button.secondary {
    background: linear-gradient(135deg, var(--accent-info) 0%, var(--blue-500) 100%);
    box-shadow: 0 4px 15px color-mix(in srgb, var(--accent-info) 40%, transparent);
}

.animated-button.accent {
    background: linear-gradient(135deg, var(--accent-error) 0%, #fb7185 100%);
    box-shadow: 0 4px 15px color-mix(in srgb, var(--accent-error) 40%, transparent);
}

.animated-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px color-mix(in srgb, var(--accent-primary) 50%, transparent);
}

.animated-button.secondary:hover {
    box-shadow: 0 8px 25px color-mix(in srgb, var(--accent-info) 50%, transparent);
}

.animated-button.accent:hover {
    box-shadow: 0 8px 25px color-mix(in srgb, var(--accent-error) 50%, transparent);
}

.animated-button:active {
    transform: translateY(0);
}

.button-shine {
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(
        45deg,
        transparent 30%,
        rgba(255, 255, 255, 0.3) 50%,
        transparent 70%
    );
    animation: button-shine 3s ease infinite;
    pointer-events: none;
}

@keyframes button-shine {
    0% {
        transform: translateX(-100%) rotate(45deg);
    }
    100% {
        transform: translateX(200%) rotate(45deg);
    }
}

.button-content {
    position: relative;
    display: flex;
    align-items: center;
    gap: var(--space-2);
    z-index: 1;
}

.button-icon {
    font-size: var(--space-5);
    line-height: 1;
}

@media (prefers-reduced-motion: reduce) {
    .button-shine {
        animation: none;
    }
}
</style>
