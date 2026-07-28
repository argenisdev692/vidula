<script setup lang="ts">
/**
 * Invite user — dedicated create page (GET /users/create, CREATE_USERS). Replaces
 * the old modal: same look and flow as the other detail screens (BackLink + Volt
 * Card), with the shared UserForm doing the work. On success the backend redirects
 * to the users list. Invitation email is delivered via Brevo.
 */
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import AppHeader from '@/modules/app/components/AppHeader.vue';
import PermissionGuard from '@/modules/auth/components/PermissionGuard.vue';
import BackLink from '@/common/ui/BackLink.vue';
import Card from '@/volt/Card.vue';
import UserForm from './components/UserForm.vue';

defineOptions({ layout: AppLayout });

defineProps<{
    availableRoles: string[];
    assignableRoles: string[];
}>();
</script>

<template>
    <Head title="Invite user" />

    <AppHeader title="Invite user" subtitle="Send an activation link — the invitee sets their own password" />

    <PermissionGuard permission="CREATE_USERS">
        <template #fallback>
            <div class="empty">
                <i class="pi pi-lock" aria-hidden="true" />
                <p>You don't have permission to invite users.</p>
            </div>
        </template>

        <div class="form-page">
            <BackLink href="/users" label="Back to users" />

            <Card class="form-card">
                <template #content>
                    <UserForm mode="create" :available-roles="availableRoles" :assignable-roles="assignableRoles" />
                </template>
            </Card>
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

.form-card {
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
</style>
