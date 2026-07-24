<script setup lang="ts">
/**
 * Schedule a new meeting — dedicated create page (GET /meetings/create,
 * CREATE_MEETINGS). Accepts optional `prefill` from lead bridge or calendar
 * dateClick query params.
 */
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import AppHeader from '@/modules/app/components/AppHeader.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import BackLink from '@/common/ui/BackLink.vue';
import MeetingForm from './components/MeetingForm.vue';
import type { MeetingPrefill } from '@/modules/meeting/types';

defineOptions({ layout: AppLayout });

defineProps<{
    prefill?: MeetingPrefill | null;
}>();
</script>

<template>
    <Head title="New meeting" />

    <AppHeader title="New meeting" subtitle="Schedule an internal meeting with mixed attendees" />

    <PermissionGuard permission="CREATE_MEETINGS">
        <template #fallback>
            <div class="empty">
                <i class="pi pi-lock" aria-hidden="true" />
                <p>You don't have permission to create meetings.</p>
            </div>
        </template>

        <div class="form-page">
            <BackLink href="/meetings" label="Back to meetings" />

            <article class="card">
                <MeetingForm mode="create" :prefill="prefill" />
            </article>
        </div>
    </PermissionGuard>
</template>

<style scoped>
.form-page {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
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

@media (max-width: 640px) {
    .card {
        padding: var(--space-5) var(--space-4);
    }
}
</style>
