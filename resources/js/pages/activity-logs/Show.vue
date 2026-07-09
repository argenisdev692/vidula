<script setup lang="ts">
/**
 * Activity Log detail — a single immutable audit entry. Read-only: no delete or
 * edit (the trail is trimmed only by the retention cron). The properties and
 * attribute-change blobs are rendered as escaped, pretty-printed JSON — never
 * v-html — since they may contain user-influenced strings.
 */
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import AppHeader from '@/modules/app/components/AppHeader.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import BackLink from '@/common/ui/BackLink.vue';
import type { ActivityLogDetail } from '@/modules/activity-log/types';
import { formatDateTime } from '@/modules/activity-log/helpers/formatDateTime';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    log: ActivityLogDetail;
}>();

const subjectLabel = computed<string>(() => {
    if (!props.log.subject_type) {
        return '—';
    }

    return props.log.subject_id ? `${props.log.subject_type} #${props.log.subject_id}` : props.log.subject_type;
});

const causerLabel = computed<string>(() => props.log.causer_label ?? 'System');

function pretty(value: Record<string, unknown> | null): string {
    return value ? JSON.stringify(value, null, 2) : '—';
}
</script>

<template>
    <Head title="Activity Log Detail" />

    <AppHeader title="Activity Log Detail" subtitle="View a single audit entry" />

    <PermissionGuard permission="VIEW_ACTIVITY_LOGS">
        <template #fallback>
            <div class="empty">
                <i class="pi pi-lock" aria-hidden="true" />
                <p>You don't have permission to view this entry.</p>
            </div>
        </template>

        <div class="detail">
            <BackLink href="/activity-logs" label="Back to Activity Log" />

            <section class="card card--hero">
                <div class="avatar" aria-hidden="true">
                    <span>{{ (log.event ?? log.description).charAt(0).toUpperCase() }}</span>
                </div>
                <div>
                    <h2 class="hero-title">{{ log.event ?? 'activity' }}</h2>
                    <span class="hero-id">#{{ log.id }} · {{ log.log_name ?? 'default' }}</span>
                </div>
            </section>

            <div class="grid">
                <section class="card">
                    <h3 class="card__title">Event</h3>
                    <div class="row"><span class="label">Event</span><span class="value">{{ log.event ?? '—' }}</span></div>
                    <div class="row"><span class="label">Description</span><span class="value">{{ log.description }}</span></div>
                    <div class="row"><span class="label">Subject</span><span class="value">{{ subjectLabel }}</span></div>
                    <div class="row"><span class="label">When</span><span class="value">{{ formatDateTime(log.created_at) }}</span></div>
                </section>

                <section class="card">
                    <h3 class="card__title">Actor</h3>
                    <div class="row"><span class="label">Name</span><span class="value">{{ causerLabel }}</span></div>
                    <div class="row"><span class="label">Type</span><span class="value">{{ log.causer_type ?? '—' }}</span></div>
                    <div class="row"><span class="label">Actor ID</span><span class="value">{{ log.causer_id ?? '—' }}</span></div>
                </section>

                <section class="card card--full">
                    <h3 class="card__title">Attribute changes</h3>
                    <pre class="json">{{ pretty(log.attribute_changes) }}</pre>
                </section>

                <section class="card card--full">
                    <h3 class="card__title">Properties</h3>
                    <pre class="json">{{ pretty(log.properties) }}</pre>
                </section>
            </div>
        </div>
    </PermissionGuard>
</template>

<style scoped>
.detail {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
    width: 100%;
    max-width: 52rem;
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

.card--hero {
    display: flex;
    align-items: center;
    gap: var(--space-4);
}

.avatar {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 52px;
    height: 52px;
    border-radius: var(--radius-xl);
    background: color-mix(in srgb, var(--accent-primary) 16%, transparent);
    color: var(--accent-primary);
    font-size: var(--text-xl);
    font-weight: var(--font-bold);
    flex-shrink: 0;
}

.hero-title {
    margin: 0;
    font-size: var(--text-lg);
    font-weight: var(--font-bold);
    color: var(--text-primary);
}

.hero-id {
    font-size: var(--text-sm);
    color: var(--text-muted);
}

.grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: var(--space-5);
    align-items: start;
}

.card__title {
    margin: 0 0 var(--space-4);
    font-size: var(--text-base);
    font-weight: var(--font-semibold);
    color: var(--text-primary);
}

.row {
    display: flex;
    justify-content: space-between;
    gap: var(--space-4);
    padding: var(--space-2) 0;
    border-bottom: 1px solid var(--border-subtle);
}

.row:last-child {
    border-bottom: none;
}

.label {
    font-size: var(--text-sm);
    color: var(--text-muted);
}

.value {
    font-size: var(--text-sm);
    color: var(--text-primary);
    text-align: right;
    word-break: break-word;
}

.json {
    margin: 0;
    padding: var(--space-4);
    border-radius: var(--radius-md);
    background: var(--bg-elevated);
    border: 1px solid var(--border-subtle);
    color: var(--text-secondary);
    font-family: var(--font-mono, monospace);
    font-size: var(--text-xs);
    line-height: 1.6;
    overflow-x: auto;
    white-space: pre-wrap;
    word-break: break-word;
}

.empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-10) var(--space-6);
    color: var(--text-muted);
}

.empty .pi {
    font-size: var(--text-2xl);
}

@media (min-width: 900px) {
    .grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .card--full {
        grid-column: 1 / -1;
    }
}
</style>
