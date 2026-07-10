<script setup lang="ts">
/**
 * Availability rule detail — read-only view rendered by
 * GET /availability-rules/{uuid} (VIEW_AVAILABILITY_RULES). The handler resolves
 * the record `withTrashed`, so a suspended rule is viewable here; its status is
 * shown via a badge.
 */
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import AppHeader from '@/modules/app/components/AppHeader.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import BackLink from '@/common/ui/BackLink.vue';
import { dayLabel, formatDate, formatTime } from '@/modules/availability/helpers/availabilityFormat';
import type { SharedProps } from '@/types/inertia';
import type { AvailabilityRule } from '@/modules/availability/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    availabilityRule: AvailabilityRule;
}>();

usePage<SharedProps>();

const isSuspended = computed<boolean>(() => props.availabilityRule.deleted_at !== null);
const title = computed<string>(() => `${dayLabel(props.availabilityRule.day_of_week)} rule`);
</script>

<template>
    <Head :title="title" />

    <AppHeader title="Availability Rule" subtitle="Weekly rule detail" />

    <PermissionGuard permission="VIEW_AVAILABILITY_RULES">
        <template #fallback>
            <div class="empty">
                <i class="pi pi-lock" aria-hidden="true" />
                <p>You don't have permission to view this availability rule.</p>
            </div>
        </template>

        <div class="detail">
            <BackLink href="/availability-rules" label="Back to rules" />

            <article class="card">
                <div class="card__head">
                    <h2 class="card__title">{{ dayLabel(availabilityRule.day_of_week) }}</h2>
                    <span class="badge" :class="isSuspended ? 'badge--suspended' : 'badge--active'">
                        {{ isSuspended ? 'Suspended' : 'Active' }}
                    </span>
                </div>

                <dl class="facts">
                    <div class="fact">
                        <dt>Start time</dt>
                        <dd class="mono">{{ formatTime(availabilityRule.start_time) }}</dd>
                    </div>
                    <div class="fact">
                        <dt>End time</dt>
                        <dd class="mono">{{ formatTime(availabilityRule.end_time) }}</dd>
                    </div>
                    <div class="fact">
                        <dt>Availability</dt>
                        <dd>{{ availabilityRule.is_available ? 'Available' : 'Unavailable' }}</dd>
                    </div>
                    <div class="fact">
                        <dt>Created</dt>
                        <dd>{{ formatDate(availabilityRule.created_at) }}</dd>
                    </div>
                    <div class="fact">
                        <dt>Last updated</dt>
                        <dd>{{ formatDate(availabilityRule.updated_at ?? null) }}</dd>
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
