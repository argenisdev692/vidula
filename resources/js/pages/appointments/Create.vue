<script setup lang="ts">
/**
 * Capture a new lead — dedicated create page (GET /appointments/create,
 * CREATE_APPOINTMENTS). No modal: same look and flow as the other detail
 * screens (BackLink + card), with the shared AppointmentForm doing the work.
 * On success the backend redirects back to the form itself (see
 * AppointmentController::store), so an admin can chain multiple captures.
 */
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import AppHeader from '@/modules/app/components/AppHeader.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import BackLink from '@/common/ui/BackLink.vue';
import AppointmentForm from './components/AppointmentForm.vue';

defineOptions({ layout: AppLayout });
</script>

<template>
    <Head title="New lead" />

    <AppHeader title="New lead" subtitle="Capture a sales lead or phone-in inquiry" />

    <PermissionGuard permission="CREATE_APPOINTMENTS">
        <template #fallback>
            <div class="empty">
                <i class="pi pi-lock" aria-hidden="true" />
                <p>You don't have permission to create leads.</p>
            </div>
        </template>

        <div class="form-page">
            <BackLink href="/appointments" label="Back to appointments" />

            <article class="card">
                <AppointmentForm mode="create" />
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
