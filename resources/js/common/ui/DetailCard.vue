<script setup lang="ts">
/**
 * The read-only detail (Show) shell every entity duplicated: AppHeader, the VIEW
 * PermissionGuard + lock fallback, the centered `.detail` column with its
 * BackLink, and the glass `.card` with a title + status-badge header. It also
 * owns — via `:deep()` — the shared `.facts` / `.fact` definition-list styling
 * (including the responsive column counts, driven by `columns`), so pages keep
 * only their genuinely-specific markup (a media block, a grants section, mail /
 * tel links) and none of the ~130 repeated CSS lines.
 *
 * Layer: common/ — imports Pages/layouts + modules/{app,auth} chrome + common/ui.
 */
import AppHeader from '@/modules/app/components/AppHeader.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import BackLink from '@/common/ui/BackLink.vue';

withDefaults(
    defineProps<{
        headerTitle: string;
        headerSubtitle: string;
        permission: string;
        fallbackText: string;
        backHref: string;
        backLabel: string;
        title: string;
        /** Facts grid column count (2 · 3 · 4); collapses responsively. */
        columns?: 2 | 3 | 4;
        /** Card column max-width; wider grids use more room. */
        maxWidth?: string;
        /** Render the title in the mono family (e.g. a permission slug). */
        monoTitle?: boolean;
        /** Accent applied to a slotted title icon. */
        iconTone?: 'primary' | 'info';
    }>(),
    { columns: 2, maxWidth: '48rem', monoTitle: false, iconTone: 'primary' },
);
</script>

<template>
    <AppHeader :title="headerTitle" :subtitle="headerSubtitle" />

    <PermissionGuard :permission="permission">
        <template #fallback>
            <div class="empty">
                <i class="pi pi-lock" aria-hidden="true" />
                <p>{{ fallbackText }}</p>
            </div>
        </template>

        <div class="detail" :style="{ maxWidth }">
            <BackLink :href="backHref" :label="backLabel" />

            <article class="card" :data-cols="columns">
                <div class="card__head">
                    <h2 class="card__title" :class="{ 'card__title--mono': monoTitle }" :data-icon="iconTone">
                        <slot name="title-icon" />
                        {{ title }}
                    </h2>
                    <div class="card__badges">
                        <slot name="badges" />
                    </div>
                </div>

                <slot />
            </article>
        </div>
    </PermissionGuard>
</template>

<style scoped>
.detail {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
    width: 100%;
    margin-inline: auto;
}

.card {
    background: color-mix(in srgb, var(--bg-surface) 60%, transparent);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-2xl);
    padding: var(--space-6) var(--space-8);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
}

.card__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-4);
    margin-bottom: var(--space-5);
    flex-wrap: wrap;
}

.card__title {
    display: inline-flex;
    align-items: center;
    gap: var(--space-3);
    margin: 0;
    font-size: var(--text-xl);
    font-weight: var(--font-bold);
    color: var(--text-primary);
}

.card__title--mono {
    font-family: var(--font-mono, monospace);
    font-size: var(--text-lg);
    word-break: break-word;
}

/* Title icon accent — primary by default, info-blue when `icon-tone="info"`. */
.card__title :deep(.pi) {
    color: var(--accent-primary);
}

.card__title[data-icon='info'] :deep(.pi) {
    color: var(--accent-info);
}

.card__badges {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    flex-shrink: 0;
}

/* Shared definition-list styling for slotted `.facts` bodies. */
.card :deep(.facts) {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--space-5);
    margin: 0;
}

.card[data-cols='3'] :deep(.facts) {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

.card[data-cols='4'] :deep(.facts) {
    grid-template-columns: repeat(4, minmax(0, 1fr));
}

.card :deep(.fact--wide) {
    grid-column: 1 / -1;
}

.card :deep(.fact dt) {
    font-size: var(--text-xs);
    font-weight: var(--font-semibold);
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--text-muted);
    margin-bottom: var(--space-1);
}

.card :deep(.fact dd) {
    margin: 0;
    font-size: var(--text-sm);
    color: var(--text-primary);
    line-height: 1.5;
    word-break: break-word;
}

.card :deep(.mono) {
    font-family: var(--font-mono, monospace);
}

.card :deep(.link) {
    color: var(--accent-primary);
    text-decoration: none;
}

.card :deep(.link:hover) {
    text-decoration: underline;
}

.empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-16) var(--space-6);
    color: var(--text-muted);
}

.empty .pi {
    font-size: var(--text-3xl);
}

@media (max-width: 900px) {
    .card[data-cols='4'] :deep(.facts) {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 640px) {
    .card[data-cols='3'] :deep(.facts) {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 560px) {
    .card :deep(.facts),
    .card[data-cols='4'] :deep(.facts) {
        grid-template-columns: 1fr;
    }

    .card {
        padding: var(--space-5) var(--space-4);
    }
}
</style>
