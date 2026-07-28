<script setup lang="ts">
/**
 * Styled back-navigation link — the single reusable "← Back to X" control shared
 * by every detail (Show) page, replacing the plain text link that was previously
 * copy-pasted per page. A pill with a circular arrow badge that nudges left on
 * hover and shifts to the cyan accent.
 *
 * Layer: common/ — imports the Inertia Link only; safe for any Page caller.
 */
import { Link } from '@inertiajs/vue3';

withDefaults(
    defineProps<{
        href: string;
        label?: string;
    }>(),
    { label: 'Back' },
);
</script>

<template>
    <Link :href="href" prefetch class="back-link" :aria-label="label">
        <span class="back-link__icon" aria-hidden="true">
            <i class="pi pi-arrow-left" />
        </span>
        <span class="back-link__label">{{ label }}</span>
    </Link>
</template>

<style scoped>
.back-link {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    width: fit-content;
    padding: var(--space-1) var(--space-3) var(--space-1) var(--space-1);
    border: 1px solid var(--border-default);
    border-radius: 999px;
    background: color-mix(in srgb, var(--bg-surface) 55%, transparent);
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
    color: var(--text-secondary);
    transition: color var(--transition), border-color var(--transition), background var(--transition);
}

.back-link__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.75rem;
    height: 1.75rem;
    border-radius: 50%;
    background: color-mix(in srgb, var(--text-primary) 6%, transparent);
    color: var(--text-secondary);
    transition: transform var(--transition), background var(--transition), color var(--transition);
}

.back-link:hover {
    color: var(--accent-primary);
    border-color: color-mix(in srgb, var(--accent-primary) 45%, transparent);
}

.back-link:hover .back-link__icon {
    transform: translateX(-2px);
    background: color-mix(in srgb, var(--accent-primary) 14%, transparent);
    color: var(--accent-primary);
}

@media (prefers-reduced-motion: reduce) {
    .back-link:hover .back-link__icon {
        transform: none;
    }
}
</style>
