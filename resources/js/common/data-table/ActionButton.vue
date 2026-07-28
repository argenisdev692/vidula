<script setup lang="ts">
/**
 * Row-action icon button for CRUD tables — the single owner of the action-pill
 * look (bordered, token-tinted per `tone`, glow on hover — size stays fixed).
 * Renders an Inertia <Link> when `href` is set (navigation: view / edit page /
 * permissions), a non-interactive <span> when `disabled` (e.g. a locked system
 * role), otherwise a <button> that emits `click`.
 *
 * Authorization stays at the call site via <PermissionGuard> — this component
 * only renders the control. Replaces the ~100-line `.btn-crud-action` block that
 * was duplicated across every table.
 */
import { Link } from '@inertiajs/vue3';

type Tone = 'view' | 'edit' | 'delete' | 'restore' | 'warning' | 'neutral';

withDefaults(
    defineProps<{
        /** primeicon class, e.g. `pi pi-eye`. */
        icon: string;
        /** Accessible name — used for `aria-label`, `title` and the tooltip. */
        label: string;
        tone?: Tone;
        /** When set, renders an Inertia <Link> instead of a <button>. */
        href?: string;
        /**
         * Inertia Link prefetch (hover by default). Only applies when `href` is set.
         * @see https://inertiajs.com/docs/v3/data-props/prefetching
         */
        prefetch?: boolean | 'hover' | 'mount' | 'click' | Array<'hover' | 'mount' | 'click'>;
        /** Non-interactive locked state (no navigation, no emit). */
        disabled?: boolean;
    }>(),
    { tone: 'neutral', href: undefined, prefetch: true, disabled: false },
);

defineEmits<{ click: [] }>();
</script>

<template>
    <Link
        v-if="href && !disabled"
        v-tooltip.top="label"
        :href="href"
        :prefetch="prefetch"
        class="crud-action"
        :data-tone="tone"
        :aria-label="label"
        :title="label"
    >
        <i :class="icon" aria-hidden="true" />
    </Link>

    <span
        v-else-if="disabled"
        v-tooltip.top="label"
        class="crud-action crud-action--disabled"
        :data-tone="tone"
        :aria-label="label"
        :title="label"
        aria-disabled="true"
    >
        <i :class="icon" aria-hidden="true" />
    </span>

    <button
        v-else
        v-tooltip.top="label"
        type="button"
        class="crud-action"
        :data-tone="tone"
        :aria-label="label"
        :title="label"
        @click="$emit('click')"
    >
        <i :class="icon" aria-hidden="true" />
    </button>
</template>

<style scoped>
.crud-action {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: var(--radius-sm);
    border: 1px solid var(--border-subtle);
    background: color-mix(in srgb, var(--bg-elevated) 50%, transparent);
    color: var(--text-secondary);
    cursor: pointer;
    transition: border-color var(--transition), box-shadow var(--transition), background var(--transition);
    overflow: hidden;
}

.crud-action::after {
    content: '';
    position: absolute;
    inset: 0;
    background: currentColor;
    opacity: 0;
    border-radius: inherit;
    transition: opacity var(--transition);
}

.crud-action:hover {
    border-color: currentColor;
}

.crud-action:hover::after {
    opacity: 0.1;
}

.crud-action:active {
    background: color-mix(in srgb, currentColor 12%, transparent);
}

.crud-action:focus-visible {
    outline: 2px solid currentColor;
    outline-offset: 2px;
}

.crud-action .pi {
    position: relative;
    z-index: 1;
    font-size: 0.8rem;
}

/* tone → accent colour (glow reuses the same token) */
.crud-action[data-tone='view'] {
    color: var(--accent-info);
}

.crud-action[data-tone='view']:hover {
    box-shadow: 0 0 12px color-mix(in srgb, var(--accent-info) 30%, transparent);
}

.crud-action[data-tone='edit'] {
    color: var(--accent-primary);
}

.crud-action[data-tone='edit']:hover {
    box-shadow: 0 0 12px color-mix(in srgb, var(--accent-primary) 30%, transparent);
}

.crud-action[data-tone='delete'] {
    color: var(--accent-error);
}

.crud-action[data-tone='delete']:hover {
    box-shadow: 0 0 12px color-mix(in srgb, var(--accent-error) 30%, transparent);
}

.crud-action[data-tone='restore'] {
    color: var(--accent-success);
}

.crud-action[data-tone='restore']:hover {
    box-shadow: 0 0 12px color-mix(in srgb, var(--accent-success) 30%, transparent);
}

.crud-action[data-tone='warning'] {
    color: var(--accent-warning);
}

.crud-action[data-tone='warning']:hover {
    box-shadow: 0 0 12px color-mix(in srgb, var(--accent-warning) 30%, transparent);
}

.crud-action[data-tone='neutral'] {
    color: var(--text-disabled);
}

.crud-action--disabled {
    cursor: not-allowed;
    opacity: 0.6;
}

.crud-action--disabled:hover {
    border-color: var(--border-subtle);
    box-shadow: none;
}

.crud-action--disabled:hover::after {
    opacity: 0;
}
</style>
