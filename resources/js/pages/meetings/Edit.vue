<script setup lang="ts">
/**
 * Edit a meeting — dedicated edit page (GET /meetings/{uuid}/edit,
 * UPDATE_MEETINGS). No modal: mirrors appointments/Edit.vue. The backend also
 * enforces organizer-or-VIEW_ANY_MEETINGS object-level authorization
 * (OWASP API1) — this page assumes the route already passed that check.
 */
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import AppHeader from '@/modules/app/components/AppHeader.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import BackLink from '@/common/ui/BackLink.vue';
import MeetingForm from './components/MeetingForm.vue';
import type { MeetingEditData } from '@/modules/meeting/types';

defineOptions({ layout: AppLayout });

defineProps<{
    meeting: MeetingEditData;
}>();
</script>

<template>
    <Head :title="`Edit ${meeting.title}`" />

    <AppHeader title="Edit meeting" :subtitle="`Update ${meeting.title}`" />

    <PermissionGuard permission="UPDATE_MEETINGS">
        <template #fallback>
            <div class="empty">
                <i class="pi pi-lock" aria-hidden="true" />
                <p>You don't have permission to edit meetings.</p>
            </div>
        </template>

        <div class="form-page">
            <BackLink href="/meetings" label="Back to meetings" />

            <article class="card">
                <MeetingForm mode="edit" :meeting="meeting" />
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
