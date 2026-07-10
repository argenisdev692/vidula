<script setup lang="ts">
/**
 * The small status pill every detail (Show) page copied per-tone: Active /
 * Suspended, Read / Unread, etc. Owns the shared `.badge` styling so pages carry
 * only a `<StatusBadge :tone :label />` instead of duplicating four `.badge--*`
 * rules each.
 *
 * Layer: common/ — no imports.
 */
withDefaults(
    defineProps<{
        tone: 'success' | 'danger' | 'muted' | 'primary';
        label: string;
        /** Heavier weight for emphasis (e.g. an unread badge). */
        strong?: boolean;
    }>(),
    { strong: false },
);
</script>

<template>
    <span class="badge" :class="[`badge--${tone}`, { 'badge--strong': strong }]">{{ label }}</span>
</template>

<style scoped>
.badge {
    display: inline-flex;
    align-items: center;
    padding: 2px var(--space-3);
    border-radius: var(--radius-sm);
    font-size: var(--text-xs);
    font-weight: var(--font-medium);
}

.badge--strong {
    font-weight: var(--font-semibold);
}

.badge--success {
    background: color-mix(in srgb, var(--accent-success) 18%, transparent);
    color: var(--accent-success);
}

.badge--danger {
    background: color-mix(in srgb, var(--accent-error) 18%, transparent);
    color: var(--accent-error);
}

.badge--muted {
    background: color-mix(in srgb, var(--text-muted) 16%, transparent);
    color: var(--text-secondary);
}

.badge--primary {
    background: color-mix(in srgb, var(--accent-primary) 18%, transparent);
    color: var(--accent-primary);
}
</style>
