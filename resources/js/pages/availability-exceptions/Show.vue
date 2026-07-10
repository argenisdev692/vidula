<script setup lang="ts">
/**
 * Date exception detail — read-only view rendered by
 * GET /availability-exceptions/{uuid} (VIEW_AVAILABILITY_EXCEPTIONS). The handler
 * resolves the record `withTrashed`, so a suspended exception is viewable here;
 * its status is shown via a badge, its provenance via a Source badge.
 */
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import AppHeader from '@/modules/app/components/AppHeader.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import BackLink from '@/common/ui/BackLink.vue';
import { formatDate, formatTime } from '@/modules/availability/helpers/availabilityFormat';
import type { SharedProps } from '@/types/inertia';
import type { AvailabilityException } from '@/modules/availability/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    availabilityException: AvailabilityException;
}>();

usePage<SharedProps>();

const isSuspended = computed<boolean>(() => props.availabilityException.deleted_at !== null);
const hours = computed<string>(() => {
    const e = props.availabilityException;
    if (!e.is_available) {
        return 'Closed all day';
    }
    if (!e.start_time || !e.end_time) {
        return '—';
    }
    return `${formatTime(e.start_time)}–${formatTime(e.end_time)}`;
});
</script>

<template>
    <Head :title="formatDate(availabilityException.date)" />

    <AppHeader title="Availability Exception" subtitle="Date override detail" />

    <PermissionGuard permission="VIEW_AVAILABILITY_EXCEPTIONS">
        <template #fallback>
            <div class="empty">
                <i class="pi pi-lock" aria-hidden="true" />
                <p>You don't have permission to view this availability exception.</p>
            </div>
        </template>

        <div class="detail">
            <BackLink href="/availability-exceptions" label="Back to exceptions" />

            <article class="card">
                <div class="card__head">
                    <h2 class="card__title">{{ formatDate(availabilityException.date) }}</h2>
                    <span class="badge" :class="isSuspended ? 'badge--suspended' : 'badge--active'">
                        {{ isSuspended ? 'Suspended' : 'Active' }}
                    </span>
                </div>

                <dl class="facts">
                    <div class="fact">
                        <dt>State</dt>
                        <dd>{{ availabilityException.is_available ? 'Open (forced available)' : 'Closed' }}</dd>
                    </div>
                    <div class="fact">
                        <dt>Hours</dt>
                        <dd class="mono">{{ hours }}</dd>
                    </div>
                    <div class="fact">
                        <dt>Source</dt>
                        <dd>{{ availabilityException.source === 'holiday' ? 'Holiday (system)' : 'Manual' }}</dd>
                    </div>
                    <div class="fact">
                        <dt>Created</dt>
                        <dd>{{ formatDate(availabilityException.created_at) }}</dd>
                    </div>
                    <div class="fact fact--wide">
                        <dt>Reason</dt>
                        <dd>{{ availabilityException.reason || '—' }}</dd>
                    </div>
                </dl>
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
    max-width: 48rem;
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
}

.card__title {
    margin: 0;
    font-size: var(--text-xl);
    font-weight: var(--font-bold);
    color: var(--text-primary);
}

.facts {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--space-5);
    margin: 0;
}

.fact--wide {
    grid-column: 1 / -1;
}

.fact dt {
    font-size: var(--text-xs);
    font-weight: var(--font-semibold);
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--text-muted);
    margin-bottom: var(--space-1);
}

.fact dd {
    margin: 0;
    font-size: var(--text-sm);
    color: var(--text-primary);
    line-height: 1.5;
}

.mono {
    font-family: var(--font-mono, monospace);
}

.badge {
    display: inline-flex;
    align-items: center;
    padding: 2px var(--space-3);
    border-radius: var(--radius-sm);
    font-size: var(--text-xs);
    font-weight: var(--font-medium);
}

.badge--active {
    background: color-mix(in srgb, var(--accent-success) 18%, transparent);
    color: var(--accent-success);
}

.badge--suspended {
    background: color-mix(in srgb, var(--accent-error) 18%, transparent);
    color: var(--accent-error);
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

@media (max-width: 560px) {
    .facts {
        grid-template-columns: 1fr;
    }

    .card {
        padding: var(--space-5) var(--space-4);
    }
}
</style>
