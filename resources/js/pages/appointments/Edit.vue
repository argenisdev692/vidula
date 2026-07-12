<script setup lang="ts">
/**
 * Edit a lead's profile — dedicated edit page (GET /appointments/{uuid}/edit,
 * UPDATE_APPOINTMENTS). No modal: mirrors Create.vue. Pipeline state
 * (status_lead, meeting_status, scheduled_at, read/spam) is never editable
 * here — see AppointmentForm's header comment.
 */
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import AppHeader from '@/modules/app/components/AppHeader.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import BackLink from '@/common/ui/BackLink.vue';
import AppointmentForm from './components/AppointmentForm.vue';
import type { AppointmentEditData } from '@/modules/appointments/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    appointment: AppointmentEditData;
}>();

const fullName = computed<string>(() => `${props.appointment.first_name} ${props.appointment.last_name}`.trim());
</script>

<template>
    <Head :title="`Edit ${fullName}`" />

    <AppHeader title="Edit lead" :subtitle="`Update ${fullName}'s profile`" />

    <PermissionGuard permission="UPDATE_APPOINTMENTS">
        <template #fallback>
            <div class="empty">
                <i class="pi pi-lock" aria-hidden="true" />
                <p>You don't have permission to edit leads.</p>
            </div>
        </template>

        <div class="form-page">
            <BackLink href="/appointments" label="Back to appointments" />

            <article class="card">
                <AppointmentForm mode="edit" :appointment="appointment" />
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
